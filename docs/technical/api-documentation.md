# Flashcard System API Documentation

## Table of Contents
1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Base URLs](#base-urls)
4. [Response Format](#response-format)
5. [Error Handling](#error-handling)
6. [Core Endpoints](#core-endpoints)
7. [Import/Export Endpoints](#importexport-endpoints)
8. [Print/PDF Endpoints](#printpdf-endpoints)
9. [Search Endpoints](#search-endpoints)
10. [Performance Endpoints](#performance-endpoints)
11. [Cache Management](#cache-management)
12. [Rate Limiting](#rate-limiting)
13. [SDKs and Examples](#sdks-and-examples)

## Overview

The Flashcard API provides programmatic access to all flashcard management, import/export, review, and printing functionality. All endpoints follow RESTful conventions and return JSON responses.

### API Version
Current version: `v1`
Base path: `/api/units/{unitId}/flashcards`

### Supported Operations
- CRUD operations for flashcards
- Bulk import/export functionality  
- Search and filtering
- PDF generation for printing
- Performance metrics and caching
- Review system integration

## Authentication

All API requests require authentication via Laravel session or API token.

### Session Authentication
```javascript
// For web requests, ensure CSRF token is included
fetch('/api/units/1/flashcards', {
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Content-Type': 'application/json'
    }
})
```

### API Token Authentication
```javascript
fetch('/api/units/1/flashcards', {
    headers: {
        'Authorization': 'Bearer YOUR_API_TOKEN',
        'Content-Type': 'application/json'
    }
})
```

## Base URLs

### Development
```
http://localhost:8000/api/units/{unitId}/flashcards
```

### Production
```
https://yourdomain.com/api/units/{unitId}/flashcards
```

## Response Format

All API responses follow a consistent JSON structure:

### Success Response
```json
{
    "success": true,
    "data": {
        // Response data
    },
    "meta": {
        "total": 100,
        "per_page": 20,
        "current_page": 1,
        "last_page": 5
    }
}
```

### Error Response
```json
{
    "success": false,
    "error": {
        "message": "Validation failed",
        "code": "VALIDATION_ERROR",
        "details": {
            "question": ["The question field is required."]
        }
    }
}
```

## Error Handling

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Access denied (e.g., kids mode) |
| 404 | Not Found | Resource not found |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Error Codes

| Code | Description |
|------|-------------|
| `VALIDATION_ERROR` | Request validation failed |
| `RESOURCE_NOT_FOUND` | Requested resource doesn't exist |
| `PERMISSION_DENIED` | Insufficient permissions |
| `RATE_LIMIT_EXCEEDED` | Too many requests |
| `IMPORT_FAILED` | Import operation failed |
| `EXPORT_FAILED` | Export operation failed |
| `DUPLICATE_DETECTED` | Duplicate flashcard found |

## Core Endpoints

### List Flashcards

Get all flashcards for a unit with pagination and filtering.

```http
GET /api/units/{unitId}/flashcards
```

#### Parameters

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `page` | integer | Page number | 1 |
| `per_page` | integer | Items per page (max 100) | 20 |
| `card_type` | string | Filter by card type | all |
| `difficulty` | string | Filter by difficulty | all |
| `is_active` | boolean | Filter by active status | true |
| `tags` | string | Comma-separated tag filter | - |
| `search` | string | Search in questions/answers | - |

#### Example Request
```bash
curl -X GET "/api/units/1/flashcards?page=1&per_page=20&card_type=basic&difficulty=medium" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Example Response
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "unit_id": 1,
            "card_type": "basic",
            "question": "What is the capital of France?",
            "answer": "Paris",
            "hint": "City of lights",
            "difficulty_level": "medium",
            "tags": ["geography", "europe"],
            "is_active": true,
            "created_at": "2025-09-09T10:00:00Z",
            "updated_at": "2025-09-09T10:00:00Z"
        }
    ],
    "meta": {
        "total": 50,
        "per_page": 20,
        "current_page": 1,
        "last_page": 3
    }
}
```

### Get Single Flashcard

```http
GET /api/units/{unitId}/flashcards/{flashcardId}
```

#### Example Response
```json
{
    "success": true,
    "data": {
        "id": 1,
        "unit_id": 1,
        "card_type": "multiple_choice",
        "question": "Which are prime numbers?",
        "answer": "2 and 5",
        "choices": ["2", "4", "5", "9"],
        "correct_choices": [0, 2],
        "hint": "Numbers divisible only by 1 and themselves",
        "difficulty_level": "medium",
        "tags": ["math", "prime-numbers"],
        "is_active": true,
        "created_at": "2025-09-09T10:00:00Z",
        "updated_at": "2025-09-09T10:00:00Z"
    }
}
```

### Create Flashcard

```http
POST /api/units/{unitId}/flashcards
```

#### Request Body
```json
{
    "card_type": "basic",
    "question": "What is 2 + 2?",
    "answer": "4",
    "hint": "Simple addition",
    "difficulty_level": "easy",
    "tags": ["math", "addition"]
}
```

#### Multiple Choice Example
```json
{
    "card_type": "multiple_choice",
    "question": "Which are even numbers?",
    "choices": ["1", "2", "3", "4"],
    "correct_choices": [1, 3],
    "hint": "Divisible by 2",
    "difficulty_level": "easy",
    "tags": ["math", "even-numbers"]
}
```

#### Cloze Deletion Example
```json
{
    "card_type": "cloze",
    "cloze_text": "The {{capital}} of France is {{Paris}}",
    "cloze_answers": ["capital", "Paris"],
    "hint": "European geography",
    "difficulty_level": "medium",
    "tags": ["geography"]
}
```

### Update Flashcard

```http
PUT /api/units/{unitId}/flashcards/{flashcardId}
```

#### Request Body
```json
{
    "question": "Updated question text",
    "answer": "Updated answer text",
    "difficulty_level": "hard"
}
```

### Delete Flashcard

```http
DELETE /api/units/{unitId}/flashcards/{flashcardId}
```

Performs soft delete. Flashcard is hidden but preserved for data integrity.

### Restore Flashcard

```http
POST /api/units/{unitId}/flashcards/{flashcardId}/restore
```

### Force Delete Flashcard

```http
DELETE /api/units/{unitId}/flashcards/{flashcardId}/force
```

Permanently removes flashcard and all associated data.

### Bulk Operations

#### Bulk Status Update
```http
PUT /api/units/{unitId}/flashcards/bulk-status
```

```json
{
    "flashcard_ids": [1, 2, 3, 4, 5],
    "is_active": false
}
```

## Import/Export Endpoints

### Import Preview

Preview import data before executing.

```http
POST /api/units/{unitId}/flashcards/import/preview
```

#### File Upload
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('import_method', 'file');

fetch('/api/units/1/flashcards/import/preview', {
    method: 'POST',
    body: formData,
    headers: {
        'X-CSRF-TOKEN': csrfToken
    }
})
```

#### Text Import
```json
{
    "import_method": "paste",
    "content": "Question 1\tAnswer 1\nQuestion 2\tAnswer 2",
    "format": "quizlet"
}
```

#### Response
```json
{
    "success": true,
    "data": {
        "detected_format": "quizlet",
        "detected_delimiter": "tab",
        "total_rows": 2,
        "valid_cards": 2,
        "invalid_cards": 0,
        "preview": [
            {
                "row": 1,
                "card_type": "basic",
                "question": "Question 1",
                "answer": "Answer 1",
                "valid": true,
                "errors": []
            }
        ],
        "errors": [],
        "warnings": []
    }
}
```

### Execute Import

```http
POST /api/units/{unitId}/flashcards/import/execute
```

```json
{
    "import_method": "paste",
    "content": "Question 1\tAnswer 1\nQuestion 2\tAnswer 2",
    "format": "quizlet",
    "duplicate_strategy": "skip",
    "default_difficulty": "medium"
}
```

#### Response
```json
{
    "success": true,
    "data": {
        "imported_count": 2,
        "skipped_count": 0,
        "error_count": 0,
        "duplicates_found": 0,
        "import_summary": {
            "basic": 2,
            "multiple_choice": 0,
            "cloze": 0
        }
    }
}
```

### Export Flashcards

```http
POST /api/units/{unitId}/flashcards/export
```

```json
{
    "format": "anki",
    "flashcard_ids": [1, 2, 3],
    "include_media": true,
    "include_metadata": true
}
```

#### Supported Formats
- `anki` - Anki package (.apkg)
- `quizlet` - Tab-delimited text
- `csv` - Comma-separated values
- `json` - JSON format
- `mnemosyne` - Mnemosyne XML
- `pdf` - PDF document

#### Response
```json
{
    "success": true,
    "data": {
        "download_url": "/storage/exports/flashcards_20250909.apkg",
        "file_size": 1024768,
        "export_count": 3,
        "format": "anki",
        "expires_at": "2025-09-10T10:00:00Z"
    }
}
```

## Print/PDF Endpoints

### Print Preview

```http
POST /api/units/{unitId}/flashcards/print/preview
```

```json
{
    "flashcard_ids": [1, 2, 3, 4, 5],
    "layout": "index_cards",
    "paper_size": "letter",
    "include_hints": true
}
```

#### Layout Options
- `index_cards` - Traditional 3x5 or 4x6 index cards
- `foldable` - Two-sided foldable cards
- `grid` - 6 cards per page grid
- `study_sheet` - List format with answers

#### Response
```json
{
    "success": true,
    "data": {
        "preview_url": "/storage/previews/print_preview_123.pdf",
        "card_count": 5,
        "page_count": 2,
        "layout": "index_cards",
        "estimated_size": "8.5x11 inches"
    }
}
```

### Generate PDF

```http
POST /api/units/{unitId}/flashcards/print/download
```

```json
{
    "flashcard_ids": [1, 2, 3, 4, 5],
    "layout": "index_cards",
    "paper_size": "letter",
    "include_hints": true,
    "duplex_printing": true
}
```

#### Response
```json
{
    "success": true,
    "data": {
        "download_url": "/storage/prints/flashcards_print_20250909.pdf",
        "file_size": 2048576,
        "card_count": 5,
        "page_count": 2
    }
}
```

## Search Endpoints

### Basic Search

```http
GET /api/units/{unitId}/flashcards/search?q=capital&type=basic
```

#### Parameters
| Parameter | Type | Description |
|-----------|------|-------------|
| `q` | string | Search query |
| `type` | string | Card type filter |
| `difficulty` | string | Difficulty filter |
| `tags` | string | Tag filter |
| `limit` | integer | Result limit (max 100) |

### Advanced Search

```http
POST /api/units/{unitId}/flashcards/search/advanced
```

```json
{
    "query": "capital",
    "filters": {
        "card_types": ["basic", "multiple_choice"],
        "difficulty_levels": ["medium", "hard"],
        "tags": ["geography"],
        "date_range": {
            "start": "2025-01-01",
            "end": "2025-12-31"
        }
    },
    "sort": {
        "field": "created_at",
        "direction": "desc"
    },
    "limit": 50
}
```

### Search Suggestions

```http
GET /api/units/{unitId}/flashcards/search/suggestions?q=cap
```

Returns autocomplete suggestions based on existing questions and answers.

## Performance Endpoints

### Performance Metrics

```http
GET /api/units/{unitId}/flashcards/metrics/performance
```

#### Response
```json
{
    "success": true,
    "data": {
        "total_flashcards": 150,
        "active_flashcards": 142,
        "cards_by_type": {
            "basic": 75,
            "multiple_choice": 45,
            "cloze": 20,
            "true_false": 10
        },
        "cards_by_difficulty": {
            "easy": 50,
            "medium": 70,
            "hard": 30
        },
        "recent_activity": {
            "created_this_week": 12,
            "reviewed_this_week": 89
        }
    }
}
```

### Error Statistics

```http
GET /api/units/{unitId}/flashcards/metrics/errors
```

Returns error rates, common validation issues, and performance bottlenecks.

## Cache Management

### Warm Cache

```http
POST /api/units/{unitId}/flashcards/cache/warm
```

Pre-loads frequently accessed flashcard data into cache.

### Clear Cache

```http
DELETE /api/units/{unitId}/flashcards/cache/clear
```

Clears all cached flashcard data for the unit.

## Rate Limiting

### Limits

| Endpoint Type | Requests per Minute | Burst Limit |
|---------------|-------------------|-------------|
| Read operations | 120 | 180 |
| Write operations | 60 | 90 |
| Import/Export | 10 | 15 |
| PDF Generation | 5 | 10 |

### Headers

Rate limit information is included in response headers:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1694259600
```

### Exceeded Response

```json
{
    "success": false,
    "error": {
        "message": "Rate limit exceeded",
        "code": "RATE_LIMIT_EXCEEDED",
        "retry_after": 60
    }
}
```

## SDKs and Examples

### JavaScript/TypeScript

```typescript
class FlashcardAPI {
    private baseUrl: string;
    private token: string;

    constructor(baseUrl: string, token: string) {
        this.baseUrl = baseUrl;
        this.token = token;
    }

    async getFlashcards(unitId: number, options: any = {}): Promise<any> {
        const params = new URLSearchParams(options);
        const response = await fetch(
            `${this.baseUrl}/api/units/${unitId}/flashcards?${params}`,
            {
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                }
            }
        );
        return response.json();
    }

    async createFlashcard(unitId: number, data: any): Promise<any> {
        const response = await fetch(
            `${this.baseUrl}/api/units/${unitId}/flashcards`,
            {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            }
        );
        return response.json();
    }

    async importFlashcards(unitId: number, importData: any): Promise<any> {
        const response = await fetch(
            `${this.baseUrl}/api/units/${unitId}/flashcards/import/execute`,
            {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.token}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(importData)
            }
        );
        return response.json();
    }
}

// Usage
const api = new FlashcardAPI('https://yourdomain.com', 'your-token');

// Get flashcards
const flashcards = await api.getFlashcards(1, {
    page: 1,
    per_page: 20,
    card_type: 'basic'
});

// Create flashcard
const newCard = await api.createFlashcard(1, {
    card_type: 'basic',
    question: 'What is the capital of Spain?',
    answer: 'Madrid',
    difficulty_level: 'medium'
});
```

### PHP

```php
<?php

class FlashcardAPIClient
{
    private $baseUrl;
    private $token;

    public function __construct(string $baseUrl, string $token)
    {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
    }

    public function getFlashcards(int $unitId, array $options = []): array
    {
        $url = "{$this->baseUrl}/api/units/{$unitId}/flashcards";
        if (!empty($options)) {
            $url .= '?' . http_build_query($options);
        }

        $response = $this->makeRequest('GET', $url);
        return json_decode($response, true);
    }

    public function createFlashcard(int $unitId, array $data): array
    {
        $url = "{$this->baseUrl}/api/units/{$unitId}/flashcards";
        $response = $this->makeRequest('POST', $url, $data);
        return json_decode($response, true);
    }

    private function makeRequest(string $method, string $url, array $data = null): string
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ]
        ]);

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}

// Usage
$api = new FlashcardAPIClient('https://yourdomain.com', 'your-token');

// Get flashcards
$flashcards = $api->getFlashcards(1, [
    'page' => 1,
    'per_page' => 20,
    'card_type' => 'basic'
]);

// Create flashcard
$newCard = $api->createFlashcard(1, [
    'card_type' => 'basic',
    'question' => 'What is the capital of Italy?',
    'answer' => 'Rome',
    'difficulty_level' => 'medium'
]);
```

### Python

```python
import requests
from typing import Dict, List, Optional

class FlashcardAPI:
    def __init__(self, base_url: str, token: str):
        self.base_url = base_url
        self.token = token
        self.headers = {
            'Authorization': f'Bearer {token}',
            'Content-Type': 'application/json'
        }

    def get_flashcards(self, unit_id: int, **params) -> Dict:
        """Get flashcards for a unit with optional filtering"""
        url = f"{self.base_url}/api/units/{unit_id}/flashcards"
        response = requests.get(url, headers=self.headers, params=params)
        response.raise_for_status()
        return response.json()

    def create_flashcard(self, unit_id: int, data: Dict) -> Dict:
        """Create a new flashcard"""
        url = f"{self.base_url}/api/units/{unit_id}/flashcards"
        response = requests.post(url, headers=self.headers, json=data)
        response.raise_for_status()
        return response.json()

    def import_flashcards(self, unit_id: int, import_data: Dict) -> Dict:
        """Import flashcards from external format"""
        url = f"{self.base_url}/api/units/{unit_id}/flashcards/import/execute"
        response = requests.post(url, headers=self.headers, json=import_data)
        response.raise_for_status()
        return response.json()

# Usage
api = FlashcardAPI('https://yourdomain.com', 'your-token')

# Get flashcards
flashcards = api.get_flashcards(1, page=1, per_page=20, card_type='basic')

# Create flashcard
new_card = api.create_flashcard(1, {
    'card_type': 'basic',
    'question': 'What is the capital of Germany?',
    'answer': 'Berlin',
    'difficulty_level': 'medium'
})
```

## Webhook Support

### Import Completion Webhook

Register webhook to receive notifications when large import operations complete:

```json
{
    "event": "import.completed",
    "unit_id": 1,
    "import_id": "abc123",
    "status": "success",
    "imported_count": 150,
    "error_count": 2,
    "timestamp": "2025-09-09T10:00:00Z"
}
```

### Export Ready Webhook

```json
{
    "event": "export.ready",
    "unit_id": 1,
    "export_id": "def456",
    "download_url": "/storage/exports/flashcards_20250909.apkg",
    "file_size": 1024768,
    "timestamp": "2025-09-09T10:00:00Z"
}
```

## Testing

### Test Environment
```
Base URL: https://api-test.yourdomain.com
Test Token: test_token_123456789
```

### Sample Test Data
The API provides test endpoints with sample data for development:

```http
GET /api/test/units/1/flashcards/sample
```

This returns a collection of sample flashcards in all supported card types for testing purposes.

## Support

For API support:
- Documentation: [https://docs.yourdomain.com/api](https://docs.yourdomain.com/api)
- Support Email: api-support@yourdomain.com
- Discord: [#api-support](https://discord.gg/yourdomain)
- GitHub Issues: [https://github.com/yourdomain/learning-app/issues](https://github.com/yourdomain/learning-app/issues)

## Changelog

### v1.2.0 (2025-09-09)
- Added advanced search functionality
- Improved error handling and validation
- Added webhook support for async operations
- Performance optimizations for large datasets

### v1.1.0 (2025-08-15)
- Added bulk operations support
- Implemented rate limiting
- Added export statistics endpoint
- Enhanced import preview functionality

### v1.0.0 (2025-07-01)
- Initial API release
- Full CRUD operations
- Import/export functionality
- PDF generation
- Search capabilities