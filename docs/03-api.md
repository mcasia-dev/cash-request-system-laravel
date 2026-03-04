# OCR Space API Documentation

## 1. Overview
This document describes how to integrate OCR Space for receipt/image text extraction in this system.

## 2. Base Endpoints
- `POST https://api.ocr.space/parse/image`
- `GET https://api.ocr.space/parse/imageurl`

Use `POST /parse/image` for production integrations.

## 3. Authentication
Send your OCR Space API key in header:

```http
apikey: YOUR_OCR_SPACE_API_KEY
```

## 4. Input Modes
Provide one input source only:
- `file` (multipart upload)
- `url` (image/PDF URL)
- `base64Image` (data URI format)

## 5. Parameters

### 5.1 Required
| Parameter | Location | Type | Required | Description |
|---|---|---|---|---|
| `apikey` | Header | string | Yes | OCR Space API key |
| `file` OR `url` OR `base64Image` | Body/Query | string/file | Yes | Input to OCR |

### 5.2 Optional
| Parameter | Type | Default | Description |
|---|---|---|---|
| `language` | string | `eng` | OCR language (`eng`, `auto`, etc.) |
| `isOverlayRequired` | bool | `false` | Include word-level coordinates |
| `filetype` | string | auto | Force file type (`PDF`, `JPG`, `PNG`, etc.) |
| `detectOrientation` | bool | `false` | Auto-detect text orientation |
| `isCreateSearchablePdf` | bool | `false` | Return searchable PDF output URL |
| `isSearchablePdfHideTextLayer` | bool | `false` | Hide text layer in generated PDF |
| `scale` | bool | `true` | Upscale low-resolution image |
| `isTable` | bool | `true` | Better table/receipt extraction |
| `OCREngine` | int | `2` | OCR engine (`1`, `2`, `3`) |

## 6. Recommended Receipt OCR Settings
For liquidation receipts:
- `language=eng`
- `isTable=true`
- `scale=true`
- `isOverlayRequired=false`
- `OCREngine=2`

For mixed-language receipts:
- `language=auto`
- `OCREngine=2` (or `3`)

## 7. Request Examples

### 7.1 Multipart File Upload
```bash
curl -X POST "https://api.ocr.space/parse/image" \
  -H "apikey: YOUR_OCR_SPACE_API_KEY" \
  -F "file=@receipt.jpg" \
  -F "language=eng" \
  -F "isTable=true" \
  -F "scale=true" \
  -F "OCREngine=2"
```

### 7.2 Image URL
```bash
curl -X POST "https://api.ocr.space/parse/image" \
  -H "apikey: YOUR_OCR_SPACE_API_KEY" \
  -F "url=https://example.com/receipt.jpg" \
  -F "language=eng" \
  -F "isTable=true"
```

### 7.3 Base64 Image
```bash
curl -X POST "https://api.ocr.space/parse/image" \
  -H "apikey: YOUR_OCR_SPACE_API_KEY" \
  -F "base64Image=data:image/jpeg;base64,/9j/4AAQSk..." \
  -F "language=eng" \
  -F "isTable=true"
```

### 7.4 GET Endpoint Example
```text
https://api.ocr.space/parse/imageurl?apikey=YOUR_OCR_SPACE_API_KEY&url=https://example.com/receipt.jpg&language=eng&isOverlayRequired=false
```

## 8. Response Structure
Top-level fields:
- `OCRExitCode`
- `IsErroredOnProcessing`
- `ErrorMessage`
- `ErrorDetails`
- `ParsedResults[]`
- `SearchablePDFURL` (if PDF creation enabled)
- `ProcessingTimeInMilliseconds`

`ParsedResults[]` common fields:
- `FileParseExitCode`
- `ParsedText`
- `TextOverlay` (when `isOverlayRequired=true`)
- `ErrorMessage`
- `ErrorDetails`

## 9. Exit Codes

### 9.1 OCRExitCode
- `1`: Success
- `2`: Parsed partially
- `3`: Failed
- `4`: Fatal error

### 9.2 FileParseExitCode
- `0`: File not found
- `1`: Success
- `-10`: Parse error
- `-20`: Timeout
- `-30`: Validation error
- `-99`: Unknown error

## 10. Suggested `.env` Keys
```env
OCR_SPACE_API_KEY=
OCR_SPACE_ENDPOINT=https://api.ocr.space/parse/image

## 11. Suggested Integration Notes for This Project
- Add `app/Services/Ocr/OcrSpaceService.php`.
- Send uploaded liquidation receipt images to OCR Space.
- Capture `ParsedText` and store OCR output in DB (or JSON field / activity log).
- Add retries + timeout handling around OCR requests.
