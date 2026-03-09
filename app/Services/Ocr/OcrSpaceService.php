<?php
namespace App\Services\Ocr;

use App\Models\LiquidationReceipt;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

class OcrSpaceService
{
    /**
     * Clear invalid receipt upload state and remove stored file when available.
     */
    private function clearInvalidReceiptUpload($state, ?string $path, Set $set, ?string $receiptFieldPath = null): void
    {
        $targetField = $receiptFieldPath ?: 'receipt';

        $set($targetField, null);
        $set($targetField, []);
        $set('receipt_number', null);

        if ($state instanceof TemporaryUploadedFile && method_exists($state, 'delete')) {
            $state->delete();

            return;
        }

        if (is_string($path) && str_starts_with($path, 'liquidation-receipts/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Parse an image file using OCR.space and return extracted text.
     */
    public function extractTextFromImage(string $absoluteImagePath): string
    {
        Log::info('OCR.space request started', [
            'image_path' => $absoluteImagePath,
        ]);

        $apiKey = (string) config('ocr-space.ocr_space_api_key');

        if ($apiKey === '') {
            throw new RuntimeException('OCR API key is not configured.');
        }

        if (! is_readable($absoluteImagePath)) {
            throw new RuntimeException('Receipt image is not readable.');
        }

        try {
            $response = Http::retry(2, 1000)
                ->timeout(45)
                ->connectTimeout(15)
                ->asMultipart()
                ->withHeaders([
                    'apikey' => $apiKey,
                ])
                ->attach('file', file_get_contents($absoluteImagePath), basename($absoluteImagePath))
                ->post(config('ocr-space.ocr_space_endpoint'), [
                    'scale'                        => 'true',
                    'isOverlayRequired'            => 'false',
                    'isCreateSearchablePdf'        => 'false',
                    'detectOrientation'            => 'false',
                    'isSearchablePdfHideTextLayer' => 'false',
                    'OCREngine'                    => '2',
                    'isTable'                      => 'true',
                ]);
        } catch (Throwable $exception) {
            $message = $exception->getMessage();

            Log::error('OCR.space transport exception', [
                'error' => $message,
            ]);

            if (str_contains(strtolower($message), 'timed out') || str_contains($message, 'cURL error 28')) {
                throw new RuntimeException('OCR request timed out. Please try again in a few seconds.');
            }

            throw new RuntimeException('OCR request failed. Please try again.');
        }

        if ($response->failed()) {
            Log::error('OCR.space request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('OCR request failed.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            Log::error('OCR.space invalid payload', [
                'payload_type' => gettype($payload),
            ]);
            throw new RuntimeException('Invalid OCR response payload.');
        }

        if ((bool) ($payload['IsErroredOnProcessing'] ?? false)) {
            $message = $payload['ErrorMessage'] ?? 'OCR failed to process the image.';

            if (is_array($message)) {
                $message = implode(' ', array_filter($message));
            }

            Log::warning('OCR.space processing error', [
                'message' => (string) $message,
            ]);
            throw new RuntimeException((string) $message);
        }

        $text = collect($payload['ParsedResults'] ?? [])
            ->pluck('ParsedText')
            ->filter()
            ->implode("\n");

        $text = trim($text);

        Log::info('OCR.space request completed', [
            'text_length' => strlen($text),
        ]);

        return $text;
    }

    /**
     * Extract a normalized receipt number from OCR text.
     */
    public function extractReceiptNumber(string $ocrText): ?string
    {
        if (trim($ocrText) === '') {
            return null;
        }

        $lines = preg_split('/\R+/', $ocrText) ?: [];

        // Prioritize strong document-number labels first.
        $patterns = [
            '/\binvoice\s*(?:no\.?|number|id|ref|#)\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\breceipt\s*(?:no\.?|number|id|ref|#)\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bofficial\s*receipt\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\b(?:o\.?\s*r\.?|or)\s*(?:no\.?|number|#)\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\b(?:s\.?\s*i\.?|si)\s*no\.?\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bsales\s*(?:invoice|inv)\s*no\.?\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bsales\s*invoice\s*no\.?\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bsales\s*invoice\s*#\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bsales\s*invoice\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\brec\s*(?:no|#)\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\brct\s*(?:no|#)\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\binv\s*(?:no|#)\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\b(?:ref|reference)\s*(?:no\.?|number|#)\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\b(?:transaction|trans)\s*(?:no\.?|number|id|#)\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\btransaction\s*id\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\btrans\s*(?:no|#)\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\b(?:doc|document)\s*(?:no\.?|number|#)\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\border\s*(?:no\.?|number|#)\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bticket\s*(?:no\.?|number|#)\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bsales\s*(?:no\.?|number|#)\s*[:=\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bterminal\s*no\.?\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bbatch\s*no\.?\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bcheck\s*no\.?\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bpos\s*(?:no|ref)\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bregister\s*no\.?\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bref\s*#\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\breference\s*#\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bapproval\s*code\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bauth\s*(?:code|#)\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bauthorization\s*no\.?\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\btrace\s*(?:no|#)\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bstan\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\brrn\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\bretrieval\s*ref\s*no\.?\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
            '/\boriginal\s*no\.?\s*[:\-]?\s*([A-Z0-9\-\/]{3,})/i',
        ];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line, $matches) === 1) {
                    $normalized = $this->normalizeReceiptNumber($matches[1]);

                    if (! preg_match('/\d/', $normalized)) {
                        Log::warning('OCR receipt number candidate rejected (no digit)', [
                            'candidate' => $normalized,
                            'line'      => $line,
                        ]);
                        continue;
                    }

                    Log::info('OCR receipt number extracted', [
                        'receipt_number' => $normalized,
                        'line'           => $line,
                    ]);

                    return $normalized;
                }
            }
        }

        Log::warning('OCR receipt number not found');

        return null;
    }

    /**
     * Convert receipt number text into a comparison-safe format.
     */
    public function normalizeReceiptNumber(string $raw): string
    {
        return strtoupper((string) preg_replace('/[^A-Z0-9\-\/]/i', '', $raw));
    }

    public function getReceiptState($state, Set $set, Get $get, ?string $receiptFieldPath = null): bool
    {
        $path         = null;
        $absolutePath = null;

        if ($state instanceof TemporaryUploadedFile) {
            $path         = $state->getFilename();
            $absolutePath = $state->getRealPath();
        } elseif (is_array($state)) {
            $first        = reset($state);
            $path         = is_string($first) ? $first : null;
            $absolutePath = is_string($first) ? Storage::disk('public')->path($first) : null;
        } elseif (is_string($state)) {
            $path         = $state;
            $absolutePath = Storage::disk('public')->path($path);
        }

        if (blank($path) || blank($absolutePath)) {
            Log::warning('Liquidation upload validation skipped: empty receipt path', [
                'user_id' => Auth::id(),
            ]);
            return false;
        }

        try {
            Log::info('Liquidation upload validation started', [
                'user_id'      => Auth::id(),
                'receipt_path' => $path,
            ]);

            $text          = $this->extractTextFromImage($absolutePath);
            $receiptNumber = $this->extractReceiptNumber($text);

            if (blank($receiptNumber)) {
                Log::error('Liquidation upload validation failed: receipt number not detected', [
                    'user_id'      => Auth::id(),
                    'receipt_path' => $path,
                ]);

                $this->clearInvalidReceiptUpload($state, $path, $set, $receiptFieldPath);

                Notification::make()
                    ->title('Receipt number could not be detected from this image.')
                    ->danger()
                    ->send();

                return false;
            }

            $items           = collect($get('../../liquidation_items') ?? []);
            $duplicateInForm = $items
                ->pluck('receipt_number')
                ->filter()
                ->contains(fn($value) => $value === $receiptNumber);

            $duplicateInDb = LiquidationReceipt::query()
                ->with('liquidation.cashRequest')
                ->where('receipt_number', $receiptNumber)
                ->first();

            if ($duplicateInForm || $duplicateInDb) {
                Log::error('Liquidation upload validation failed: duplicate receipt number', [
                    'user_id'           => Auth::id(),
                    'receipt_path'      => $path,
                    'receipt_number'    => $receiptNumber,
                    'duplicate_in_form' => $duplicateInForm,
                    'duplicate_in_db'   => (bool) $duplicateInDb,
                ]);

                $this->clearInvalidReceiptUpload($state, $path, $set, $receiptFieldPath);

                $errorMessage = 'Receipt number has already been used in this form.';

                if ($duplicateInDb) {
                    $cashRequest = $duplicateInDb->liquidation?->cashRequest;
                    $activity = $cashRequest?->activityLists->first()->activity_name ?: 'Unknown activity';
                    $usedAt = $cashRequest?->created_at;
                    $usedAtText = $usedAt ? $usedAt->format('F d, Y h:i A') : 'an unknown date/time';

                    $errorMessage = "Receipt number has already been used in activity \"{$activity}\" on {$usedAtText}.";
                }

                Notification::make()
                    ->title('Duplicate receipt number detected')
                    ->body($errorMessage)
                    ->duration(10000)
                    ->danger()
                    ->send();

                return false;
            }

            $set('receipt_number', $receiptNumber);

            Log::info('Liquidation upload validation passed', [
                'user_id'        => Auth::id(),
                'receipt_path'   => $path,
                'receipt_number' => $receiptNumber,
            ]);
        } catch (Throwable $exception) {
            Log::error('Liquidation upload validation exception', [
                'user_id'      => Auth::id(),
                'receipt_path' => $path,
                'error'        => $exception->getMessage(),
            ]);

            $this->clearInvalidReceiptUpload($state, $path, $set, $receiptFieldPath);

            Notification::make()
                ->title('Unable to read the receipt image. Please upload a clearer image.')
                ->danger()
                ->send();

            return false;
        }

        return true;
    }
}
