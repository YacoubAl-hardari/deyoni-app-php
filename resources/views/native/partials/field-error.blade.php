@foreach($errors[$field] ?? [] as $message)
    <native:text class="text-sm text-theme-destructive">{{ $message }}</native:text>
@endforeach
