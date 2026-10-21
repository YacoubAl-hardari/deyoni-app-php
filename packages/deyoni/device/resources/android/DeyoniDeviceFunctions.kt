package com.deyoni.plugins.device

import android.app.Activity
import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import androidx.fragment.app.Fragment
import androidx.fragment.app.FragmentActivity
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.ui.platform.LocalLayoutDirection
import androidx.compose.ui.unit.LayoutDirection
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.ui.nativerender.NativeRootHostRegistry
import com.nativephp.mobile.utils.NativeActionCoordinator
import org.json.JSONObject
import java.io.File

fun registerDeyoni(context: Context) {
    NativeRootHostRegistry.register("deyoni.arabic") { _, content ->
        CompositionLocalProvider(LocalLayoutDirection provides LayoutDirection.Rtl) { content() }
    }
}

object DeyoniDeviceFunctions {
    class Appearance(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val mode = parameters["mode"] as? String ?: "system"
            Handler(Looper.getMainLooper()).post {
                val systemDark = (activity.resources.configuration.uiMode and android.content.res.Configuration.UI_MODE_NIGHT_MASK) == android.content.res.Configuration.UI_MODE_NIGHT_YES
                val dark = if (mode == "system") systemDark else mode == "dark"
                val controller = androidx.core.view.WindowCompat.getInsetsController(activity.window, activity.window.decorView)
                controller.isAppearanceLightStatusBars = !dark
                controller.isAppearanceLightNavigationBars = !dark
            }
            return mapOf("applied" to true)
        }
    }

    class Documents(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val action = parameters["action"] as? String ?: return mapOf("started" to false)
            val path = parameters["path"] as? String ?: ""
            if (action == "share-pdf") {
                Thread {
                    try {
                        val source = File(path).canonicalFile
                        require(source.path.startsWith(File(activity.applicationInfo.dataDir).canonicalPath + File.separator) && source.isFile && source.length() <= 20000000L && source.name.startsWith("statement-") && source.extension == "pdf")
                        val directory = File(activity.cacheDir, "deyoni-statements").apply { mkdirs() }
                        directory.listFiles()?.filter { it.lastModified() < System.currentTimeMillis() - 86400000L }?.forEach { it.delete() }
                        val shared = File(directory, source.name)
                        source.copyTo(shared, overwrite = true)
                        val uri = androidx.core.content.FileProvider.getUriForFile(activity, activity.packageName + ".fileprovider", shared)
                        Handler(Looper.getMainLooper()).post {
                            try {
                                val intent = Intent(Intent.ACTION_SEND).apply {
                                    type = "application/pdf"
                                    putExtra(Intent.EXTRA_STREAM, uri)
                                    clipData = android.content.ClipData.newRawUri("PDF", uri)
                                    addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
                                }
                                activity.startActivity(Intent.createChooser(intent, "مشاركة كشف الحساب"))
                                NativeActionCoordinator.dispatchEvent(activity, "Deyoni\\Device\\Events\\DocumentResult", JSONObject().put("success", true).put("path", "").put("action", action).toString())
                            } catch (_: Exception) {
                                NativeActionCoordinator.dispatchEvent(activity, "Deyoni\\Device\\Events\\DocumentResult", JSONObject().put("success", false).put("path", "").put("action", action).toString())
                            }
                        }
                    } catch (_: Exception) {
                        Handler(Looper.getMainLooper()).post {
                            NativeActionCoordinator.dispatchEvent(activity, "Deyoni\\Device\\Events\\DocumentResult", JSONObject().put("success", false).put("path", "").put("action", action).toString())
                        }
                    }
                }.start()
                return mapOf("started" to true)
            }
            if (action != "export" && action != "import" && action != "export-pdf") return mapOf("started" to false)
            Handler(Looper.getMainLooper()).post {
                if (activity.supportFragmentManager.findFragmentByTag("deyoni.documents") == null) {
                    val fragment = DeyoniDocumentFragment()
                    fragment.arguments = Bundle().apply { putString("action", action); putString("path", path) }
                    activity.supportFragmentManager.beginTransaction().add(fragment, "deyoni.documents").commitAllowingStateLoss()
                }
            }
            return mapOf("started" to true)
        }
    }
}

class DeyoniDocumentFragment : Fragment() {
    private val action: String get() = requireArguments().getString("action") ?: "import"
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        if (savedInstanceState != null) return
        val isPdf = action == "export-pdf"
        val intent = if (action == "export" || isPdf) {
            Intent(Intent.ACTION_CREATE_DOCUMENT).apply {
                addCategory(Intent.CATEGORY_OPENABLE)
                type = if (isPdf) "application/pdf" else "application/octet-stream"
                putExtra(Intent.EXTRA_TITLE, if (isPdf) "deyoni-statement.pdf" else "deyoni-backup.deyoni")
            }
        } else {
            Intent(Intent.ACTION_OPEN_DOCUMENT).apply {
                addCategory(Intent.CATEGORY_OPENABLE)
                type = "*/*"
            }
        }
        try { startActivityForResult(intent, 71) }
        catch (_: Exception) { finishResult(false, "") }
    }

    @Deprecated("Fragment activity-result integration")
    override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
        super.onActivityResult(requestCode, resultCode, data)
        if (requestCode != 71) return
        val uri = data?.data
        if (resultCode != Activity.RESULT_OK || uri == null) { finishResult(false, ""); return }
        val host = activity as? FragmentActivity ?: return
        Thread {
            var imported: File? = null
            try {
                val resultPath = if (action == "export" || action == "export-pdf") {
                    val source = File(requireArguments().getString("path") ?: "").canonicalFile
                    val privateRoot = File(host.applicationInfo.dataDir).canonicalFile
                    require(source.path.startsWith(privateRoot.path + File.separator) && source.length() <= 20000000L)
                    host.contentResolver.openOutputStream(uri, "wt")!!.use { output -> source.inputStream().use { it.copyTo(output) } }
                    ""
                } else {
                    val target = File.createTempFile("deyoni-import-", ".deyoni", host.filesDir)
                    imported = target
                    host.contentResolver.openInputStream(uri)!!.use { input ->
                        target.outputStream().use { output ->
                            val buffer = ByteArray(8192)
                            var total = 0L
                            while (true) {
                                val n = input.read(buffer)
                                if (n < 0) break
                                total += n
                                require(total <= 20000000L)
                                output.write(buffer, 0, n)
                            }
                        }
                    }
                    target.absolutePath
                }
                host.runOnUiThread { finishResult(true, resultPath) }
            } catch (_: Exception) {
                imported?.delete()
                host.runOnUiThread { finishResult(false, "") }
            }
        }.start()
    }

    private fun finishResult(success: Boolean, path: String) {
        val host = activity as? FragmentActivity ?: return
        val payload = JSONObject().put("success", success).put("path", path).put("action", action)
        NativeActionCoordinator.dispatchEvent(host, "Deyoni\\Device\\Events\\DocumentResult", payload.toString())
        parentFragmentManager.beginTransaction().remove(this).commitAllowingStateLoss()
    }
}

