package com.fixit.plugins.photo_resize

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Matrix
import android.media.ExifInterface
import android.util.Log
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import java.io.File

/**
 * Shrinks a captured JPEG before it is uploaded to the AI provider.
 *
 * The embedded PHP on Android has no GD or Imagick, so the resize runs here.
 * Namespace: "PhotoResize.*"
 */
object PhotoResizeFunctions {

    private const val TAG = "PhotoResize"

    /**
     * Downscale a JPEG in place so its long edge is at most maxEdge pixels.
     * Parameters:
     *   - path: string - absolute path of the photo (app-private storage)
     *   - maxEdge: (optional) int - longest edge in pixels, default 1568
     *   - quality: (optional) int - JPEG quality 1-100, default 85
     * Returns:
     *   - path, width, height, bytes, resized (false when the photo was already small)
     *
     * The camera's EXIF rotation is applied to the pixels; the rewritten file carries
     * no EXIF, so capture location and device data are not uploaded.
     */
    class Downscale(private val context: Context) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val path = parameters["path"] as? String
                ?: return fail("path is required")
            val maxEdge = (parameters["maxEdge"] as? Number)?.toInt()?.coerceAtLeast(64) ?: 1568
            val quality = (parameters["quality"] as? Number)?.toInt()?.coerceIn(1, 100) ?: 85

            val source = File(path)
            if (!source.isFile) {
                return fail("photo not found")
            }

            return try {
                val bounds = BitmapFactory.Options().apply { inJustDecodeBounds = true }
                BitmapFactory.decodeFile(path, bounds)
                if (bounds.outWidth <= 0 || bounds.outHeight <= 0) {
                    return fail("not a readable image")
                }

                val longEdge = maxOf(bounds.outWidth, bounds.outHeight)
                if (longEdge <= maxEdge) {
                    return BridgeResponse.success(result(source, bounds.outWidth, bounds.outHeight, false))
                }

                // Decode at a power-of-two sample size that stays above maxEdge, then scale exactly.
                var sample = 1
                while (longEdge / (sample * 2) >= maxEdge) {
                    sample *= 2
                }
                val decoded = BitmapFactory.decodeFile(path, BitmapFactory.Options().apply { inSampleSize = sample })
                    ?: return fail("not a readable image")

                val scale = maxEdge.toFloat() / maxOf(decoded.width, decoded.height)
                val matrix = Matrix().apply {
                    postScale(scale, scale)
                    postRotate(rotationDegrees(path))
                }
                val output = Bitmap.createBitmap(decoded, 0, 0, decoded.width, decoded.height, matrix, true)
                if (output !== decoded) {
                    decoded.recycle()
                }

                // Write next to the original, then replace it, so a failure leaves the photo intact.
                val temp = File(source.parentFile, "${source.name}.resize")
                temp.outputStream().buffered(64 * 1024).use { stream ->
                    output.compress(Bitmap.CompressFormat.JPEG, quality, stream)
                }
                val width = output.width
                val height = output.height
                output.recycle()

                if (!temp.renameTo(source)) {
                    temp.delete()
                    return fail("could not replace the photo")
                }

                Log.d(TAG, "Downscaled ${bounds.outWidth}x${bounds.outHeight} to ${width}x${height}, ${source.length()} bytes")
                BridgeResponse.success(result(source, width, height, true))
            } catch (e: OutOfMemoryError) {
                Log.e(TAG, "Out of memory while downscaling", e)
                fail("out of memory")
            } catch (e: Exception) {
                Log.e(TAG, "Downscale failed: ${e.message}", e)
                fail(e.message ?: "downscale failed")
            }
        }

        private fun fail(message: String): Map<String, Any> = BridgeResponse.error("RESIZE_FAILED", message)

        private fun rotationDegrees(path: String): Float =
            when (ExifInterface(path).getAttributeInt(ExifInterface.TAG_ORIENTATION, ExifInterface.ORIENTATION_NORMAL)) {
                ExifInterface.ORIENTATION_ROTATE_90 -> 90f
                ExifInterface.ORIENTATION_ROTATE_180 -> 180f
                ExifInterface.ORIENTATION_ROTATE_270 -> 270f
                else -> 0f
            }

        private fun result(file: File, width: Int, height: Int, resized: Boolean): Map<String, Any> = mapOf(
            "path" to file.absolutePath,
            "width" to width,
            "height" to height,
            "bytes" to file.length(),
            "resized" to resized,
        )
    }
}
