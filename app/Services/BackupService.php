<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class BackupService
{
    public const MAX_BYTES = 20_000_000;

    private const AAD = 'Deyoni.backup.v1';

    public function export(string $password): string
    {
        $this->password($password);
        $data = DB::transaction(fn (): array => [
            'format' => 1,
            'created_at' => now()->toIso8601String(),
            'creditors' => DB::table('creditors')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'ledger_entries' => DB::table('ledger_entries')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'audit_events' => DB::table('audit_events')->orderBy('id')->get()->map(fn ($r) => (array) $r)->all(),
            'app_settings' => DB::table('app_settings')->get()->map(fn ($r) => (array) $r)->all(),
        ]);
        $salt = random_bytes(16);
        $iv = random_bytes(12);
        $key = hash_pbkdf2('sha256', $password, $salt, 600000, 32, true);
        $cipher = openssl_encrypt(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, self::AAD);
        if ($cipher === false) {
            $this->invalid();
        }
        $result = json_encode([
            'format' => 1,
            'algorithm' => 'AES-256-GCM',
            'kdf' => 'PBKDF2-SHA256',
            'iterations' => 600000,
            'salt' => base64_encode($salt),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($cipher),
        ], JSON_THROW_ON_ERROR);
        if (strlen($result) > self::MAX_BYTES) {
            $this->invalid();
        }

        return $result;
    }

    public function inspect(string $bytes, string $password): array
    {
        $this->password($password);
        try {
            if (strlen($bytes) > self::MAX_BYTES) {
                $this->invalid();
            }
            $envelope = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
            if (($envelope['format'] ?? null) !== 1 || ($envelope['algorithm'] ?? '') !== 'AES-256-GCM' ||
                ($envelope['kdf'] ?? '') !== 'PBKDF2-SHA256' || ($envelope['iterations'] ?? null) !== 600000
            ) {
                $this->invalid();
            }
            $salt = base64_decode($envelope['salt'], true);
            $iv = base64_decode($envelope['iv'], true);
            $tag = base64_decode($envelope['tag'], true);
            $cipher = base64_decode($envelope['data'], true);
            if (strlen($salt ?: '') !== 16 || strlen($iv ?: '') !== 12 || strlen($tag ?: '') !== 16 || $cipher === false) {
                $this->invalid();
            }
            $key = hash_pbkdf2('sha256', $password, $salt, 600000, 32, true);
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, self::AAD);
            if ($plain === false) {
                $this->invalid();
            }
            $data = json_decode($plain, true, 64, JSON_THROW_ON_ERROR);
            $this->validateData($data);

            return $data;
        } catch (Throwable $e) {
            $this->invalid();
        }
    }

    public function restore(string $bytes, string $password): void
    {
        $data = $this->inspect($bytes, $password);
        usort($data['ledger_entries'], fn ($a, $b) => $a['id'] <=> $b['id']);
        $recovery = $this->export($password);
        $directory = storage_path('app/private/backups');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
            $this->invalid();
        }
        if (file_put_contents($directory.'/before-restore.deyoni', $recovery, LOCK_EX) === false) {
            $this->invalid();
        }
        DB::transaction(function () use ($data): void {
            DB::table('audit_events')->delete();
            DB::statement('UPDATE ledger_entries SET replaces_entry_id = NULL');
            DB::table('ledger_entries')->delete();
            DB::table('creditors')->delete();
            DB::table('app_settings')->delete();
            foreach (['creditors', 'ledger_entries', 'audit_events', 'app_settings'] as $table) {
                foreach ($data[$table] as $row) {
                    DB::table($table)->insert($row);
                }
            }
            if (DB::select('PRAGMA foreign_key_check') !== [] || DB::select('PRAGMA integrity_check')[0]->integrity_check !== 'ok') {
                $this->invalid();
            }
        }, 3);
    }

    public function validateData(array $data): void
    {
        Validator::make($data, [
            'format' => 'required|in:1',
            'created_at' => 'required|date',
            'creditors' => 'present|array|max:100000',
            'ledger_entries' => 'present|array|max:200000',
            'audit_events' => 'present|array|max:200000',
            'app_settings' => 'present|array|max:2',
            'creditors.*' => 'array:id,name,phone,icon,notes,archived_at,created_at,updated_at',
            'creditors.*.id' => 'required|integer|min:1|distinct',
            'creditors.*.name' => 'required|string|max:120',
            'creditors.*.phone' => 'nullable|string|max:40',
            'creditors.*.icon' => 'required|in:store,home,directions_car,directions_bus,person,category',
            'creditors.*.notes' => 'nullable|string|max:2000',
            'creditors.*.archived_at' => 'nullable|date',
            'ledger_entries.*' => 'array:id,creditor_id,kind,amount_minor,currency,occurred_at,notes,request_id,replaces_entry_id,voided_at,created_at,updated_at',
            'ledger_entries.*.id' => 'required|integer|min:1|distinct',
            'ledger_entries.*.creditor_id' => 'required|integer|min:1',
            'ledger_entries.*.kind' => 'required|in:debt,payment',
            'ledger_entries.*.amount_minor' => 'required|integer|min:1|max:1000000000000',
            'ledger_entries.*.currency' => 'required|in:YER,SAR',
            'ledger_entries.*.occurred_at' => 'required|date_format:Y-m-d',
            'ledger_entries.*.notes' => 'nullable|string|max:2000',
            'ledger_entries.*.request_id' => 'required|uuid|distinct',
            'ledger_entries.*.replaces_entry_id' => 'nullable|integer|min:1',
            'ledger_entries.*.voided_at' => 'nullable|date',
            'audit_events.*' => 'array:id,entity_type,entity_id,action,before_data,after_data,reason,created_at',
            'audit_events.*.id' => 'required|integer|min:1|distinct',
            'audit_events.*.entity_type' => 'required|in:ledger_entry',
            'audit_events.*.entity_id' => 'required|integer|min:1',
            'audit_events.*.action' => 'required|in:corrected,voided',
            'audit_events.*.before_data' => 'nullable|json',
            'audit_events.*.after_data' => 'nullable|json',
            'audit_events.*.reason' => 'required|string|max:1000',
            'audit_events.*.created_at' => 'required|date',
            'app_settings.*' => 'array:key,value',
            'app_settings.*.key' => 'required|in:appearance,currency|distinct',
            'app_settings.*.value' => 'required|string',
        ])->validate();
        $creditors = array_column($data['creditors'], null, 'id');
        $entries = array_column($data['ledger_entries'], null, 'id');
        $replaced = [];
        foreach ($data['ledger_entries'] as $entry) {
            if (! is_int($entry['amount_minor']) || ! isset($creditors[$entry['creditor_id']])) {
                $this->invalid();
            }
            if ($id = $entry['replaces_entry_id'] ?? null) {
                $old = $entries[$id] ?? null;
                if (! $old || ! $old['voided_at'] || $old['creditor_id'] !== $entry['creditor_id'] || $id >= $entry['id'] || isset($replaced[$id])) {
                    $this->invalid();
                }
                $replaced[$id] = true;
            }
        }
        foreach ($data['audit_events'] as $event) {
            if (! isset($entries[$event['entity_id']])) {
                $this->invalid();
            }
        }
        foreach ($data['app_settings'] as $setting) {
            if (! in_array($setting['value'], $setting['key'] === 'currency' ? ['YER', 'SAR'] : ['system', 'light', 'dark'], true)) {
                $this->invalid();
            }
        }
        foreach (['creditors', 'ledger_entries'] as $table) {
            foreach ($data[$table] as $row) {
                foreach (['created_at', 'updated_at'] as $field) {
                    if (! empty($row[$field]) && strtotime($row[$field]) === false) {
                        $this->invalid();
                    }
                }
            }
        }
        usort($data['ledger_entries'], fn ($a, $b) => $a['id'] <=> $b['id']);
    }

    private function password(string $password): void
    {
        Validator::make(
            ['password' => $password],
            ['password' => 'required|string|min:8|max:1024'],
            ['password.min' => 'استخدم كلمة مرور من 8 أحرف على الأقل.']
        )->validate();
        if (! extension_loaded('openssl') || ! in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            throw ValidationException::withMessages(['backup' => 'التشفير غير متاح في هذه البيئة.']);
        }
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['backup' => 'النسخة غير صالحة أو كلمة المرور غير صحيحة.']);
    }
}
