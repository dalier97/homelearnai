# Import/Export Guide - Flashcard System

## Table of Contents
1. [Overview](#overview)
2. [Import Formats](#import-formats)
3. [Export Formats](#export-formats)
4. [Format-Specific Guides](#format-specific-guides)
5. [Data Mapping](#data-mapping)
6. [Best Practices](#best-practices)
7. [Troubleshooting](#troubleshooting)
8. [Migration Strategies](#migration-strategies)

## Overview

The flashcard system supports comprehensive import and export functionality, allowing you to:

- **Import from popular platforms**: Quizlet, Anki, Mnemosyne, and more
- **Export to any format**: Share your cards across different platforms
- **Preserve data integrity**: All card types and metadata are maintained
- **Handle large datasets**: Bulk operations with progress tracking
- **Detect duplicates**: Smart detection and resolution strategies

### Supported Operations

| Operation | File Upload | Copy/Paste | URL Import | Bulk Processing |
|-----------|-------------|------------|------------|-----------------|
| Import | ✅ | ✅ | ✅ | ✅ |
| Export | ✅ | ✅ | ❌ | ✅ |

## Import Formats

### Quick Reference

| Format | Extension | Card Types | Media Support | Auto-Detection |
|--------|-----------|------------|---------------|----------------|
| Quizlet | .txt, .csv, .tsv | Basic, MC | ❌ | ✅ |
| Anki | .apkg | All types | ✅ | ✅ |
| Mnemosyne | .mem, .xml | Basic, Cloze | ✅ | ✅ |
| CSV | .csv | All types | ❌ | ✅ |
| JSON | .json | All types | ✅ | ✅ |
| SuperMemo | .txt | Basic | ❌ | ✅ |

### Import Process

1. **Choose Import Method**
   - File upload (supports all formats)
   - Copy/paste text (for simple formats)
   - URL import (for supported platforms)

2. **Auto-Detection Phase**
   - Format recognition
   - Delimiter detection
   - Card type identification
   - Media file discovery

3. **Preview & Validation**
   - See parsed results
   - Identify errors and warnings
   - Adjust import settings
   - Review duplicate detection

4. **Import Execution**
   - Bulk card creation
   - Media file processing
   - Progress tracking
   - Error reporting

## Export Formats

### Available Export Options

| Format | Purpose | Card Types | Media | File Size |
|--------|---------|------------|-------|-----------|
| Anki Package | Full-featured export | All types | ✅ | Large |
| Quizlet Text | Simple sharing | Basic only | ❌ | Small |
| CSV | Spreadsheet editing | All types | ❌ | Medium |
| JSON | Backup/API | All types | ✅ | Medium |
| PDF | Printing/sharing | All types | ✅ | Large |
| Mnemosyne | Legacy support | Basic, Cloze | ✅ | Medium |

### Export Process

1. **Select Cards**
   - Choose specific cards
   - Filter by criteria
   - Select entire units

2. **Choose Format**
   - Pick target platform
   - Configure options
   - Include/exclude media

3. **Generate Export**
   - Process cards
   - Package files
   - Generate download

## Format-Specific Guides

### Quizlet Import/Export

#### Import from Quizlet

**Method 1: Copy/Paste from Quizlet**
1. Go to your Quizlet study set
2. Click "Export" → "Copy text"
3. In our app, click "Import Flashcards"
4. Select "Copy/Paste" method
5. Paste the text into the textarea
6. Click "Preview" to validate

**Method 2: Export from Quizlet as File**
1. In Quizlet, go to your study set
2. Click "Export" → "Download"
3. Choose "Text (.txt)" format
4. In our app, upload the downloaded file

**Supported Delimiters:**
- Tab-separated: `Term[TAB]Definition`
- Comma-separated: `Term,Definition`
- Dash-separated: `Term - Definition`
- Pipe-separated: `Term|Definition`

**Example Quizlet Format:**
```
What is the capital of France?	Paris
What is 2+2?	4
Name the largest planet	Jupiter
```

#### Export to Quizlet

**Steps:**
1. Select flashcards to export
2. Choose "Quizlet" format
3. Download the text file
4. In Quizlet, create new set and import the file

**Output Format:**
```
Question text[TAB]Answer text
Multiple choice question[TAB]Correct answer (choices in question)
```

**Limitations:**
- Only basic cards export cleanly
- Multiple choice becomes basic Q&A
- Media files not supported
- Cloze cards convert to basic format

### Anki Import/Export

#### Import from Anki

**Supported Anki Features:**
- All note types (Basic, Cloze, Image Occlusion)
- Media files (images, audio, video)
- Tags and metadata
- Card scheduling data
- Custom fields

**Steps:**
1. In Anki, select your deck
2. Go to "File" → "Export"
3. Choose "Anki Deck Package (*.apkg)"
4. Include media files
5. Upload the .apkg file to our app

**Card Type Mapping:**
- **Basic** → Basic
- **Basic (and reversed card)** → Two Basic cards
- **Cloze** → Cloze deletion
- **Image Occlusion** → Image occlusion
- **Basic (type in the answer)** → Typed answer

#### Export to Anki

**Features:**
- Full card type preservation
- Media file inclusion
- Tag preservation
- Proper note type mapping

**Steps:**
1. Select cards to export
2. Choose "Anki Package" format
3. Include media files option
4. Download .apkg file
5. Import into Anki

**Generated Structure:**
```
flashcards_export.apkg
├── collection.anki21 (SQLite database)
├── media (folder)
│   ├── 1.jpg
│   ├── 2.png
│   └── ...
└── media.json (media mapping)
```

### CSV Import/Export

#### CSV Import

**Our Extended CSV Format:**
```csv
Type,Question,Answer,Choices,Correct,Hint,Tags,Difficulty
basic,"What is 2+2?","4",,,"Simple math","math,arithmetic",easy
multiple_choice,"Pick prime numbers","","2;3;4;5","0;1","Divisible by 1 and self","math",medium
cloze,"The {{sun}} is a {{star}}","sun;star",,,"Astronomy","science,space",medium
true_false,"Earth is flat","false","True;False","1","Think satellites","science",easy
```

**Field Descriptions:**
- **Type**: Card type (basic, multiple_choice, true_false, cloze, typed_answer, image_occlusion)
- **Question**: Main question text
- **Answer**: Answer text (for basic cards)
- **Choices**: Semicolon-separated options (for MC/TF)
- **Correct**: Indexes of correct choices (0;1;2)
- **Hint**: Optional hint text
- **Tags**: Comma-separated tags
- **Difficulty**: easy, medium, hard

**Flexible Import:**
- Header row optional
- Missing columns use defaults
- Auto-detection of delimiters
- Quoted fields for commas in text

#### CSV Export

**Standard Format:**
```csv
ID,Type,Question,Answer,Choices,Correct_Choices,Hint,Tags,Difficulty,Created,Updated
1,basic,"Capital of France?","Paris",,,"City of lights","geography",medium,2025-09-09,2025-09-09
2,multiple_choice,"Even numbers?","","2;3;4;6","0;2","Divisible by 2","math",easy,2025-09-09,2025-09-09
```

**Options:**
- Include metadata (IDs, dates)
- Filter by card type
- Custom delimiter choice
- Quoted/unquoted fields

### JSON Import/Export

#### JSON Format

**Complete Card Structure:**
```json
{
    "flashcards": [
        {
            "card_type": "basic",
            "question": "What is the capital of France?",
            "answer": "Paris",
            "hint": "City of lights",
            "difficulty_level": "medium",
            "tags": ["geography", "europe"],
            "metadata": {
                "created_at": "2025-09-09T10:00:00Z",
                "import_source": "manual_entry"
            }
        },
        {
            "card_type": "multiple_choice",
            "question": "Which are prime numbers?",
            "choices": ["2", "4", "5", "9"],
            "correct_choices": [0, 2],
            "hint": "Divisible only by 1 and themselves",
            "difficulty_level": "medium",
            "tags": ["math", "prime-numbers"]
        },
        {
            "card_type": "cloze",
            "cloze_text": "The {{mitochondria}} is the {{powerhouse}} of the cell",
            "cloze_answers": ["mitochondria", "powerhouse"],
            "hint": "Cellular biology",
            "difficulty_level": "hard",
            "tags": ["biology", "cells"]
        }
    ],
    "metadata": {
        "export_date": "2025-09-09T10:00:00Z",
        "version": "1.0",
        "total_cards": 3,
        "source_unit": "Biology Unit 1"
    }
}
```

**Advantages:**
- Complete data preservation
- Human-readable format
- Version control friendly
- API compatible

### Mnemosyne Import/Export

#### Mnemosyne XML Format

**Import Support:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<mnemosyne version="1.0">
    <category name="Science">
        <item>
            <Q>What is the chemical symbol for water?</Q>
            <A>H2O</A>
            <tags>chemistry, compounds</tags>
        </item>
        <item>
            <Q>The {{heart}} pumps {{blood}} through the body</Q>
            <A>heart;blood</A>
            <tags>biology, circulatory</tags>
        </item>
    </category>
</mnemosyne>
```

**Card Type Detection:**
- Cards with `{{}}` syntax → Cloze deletion
- Regular Q&A → Basic cards
- Media references → Image cards

#### Export to Mnemosyne

**Generated Format:**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<mnemosyne version="1.0">
    <category name="Exported Flashcards">
        <!-- Basic cards -->
        <item>
            <Q>Question text</Q>
            <A>Answer text</A>
            <tags>tag1, tag2</tags>
        </item>
        <!-- Cloze cards -->
        <item>
            <Q>The {{sun}} is a {{star}}</Q>
            <A>sun;star</A>
            <tags>science</tags>
        </item>
    </category>
</mnemosyne>
```

### SuperMemo Import

#### Q&A Format

**Simple Format:**
```
Q: What is the capital of France?
A: Paris

Q: What is 2+2?
A: 4

Q: Name the largest planet
A: Jupiter
```

**Extended Format with Categories:**
```
Category: Geography
Q: What is the capital of France?
A: Paris

Q: What is the capital of Germany?
A: Berlin

Category: Math
Q: What is 2+2?
A: 4
```

**Import Process:**
1. Upload .txt file or paste text
2. Auto-detection identifies Q: and A: patterns
3. Categories become tags
4. All cards import as basic type

## Data Mapping

### Card Type Conversions

#### Import Mapping

| Source Format | Source Type | Our Type | Notes |
|---------------|-------------|----------|-------|
| Anki | Basic | basic | Direct mapping |
| Anki | Cloze | cloze | Preserves {{}} syntax |
| Anki | Type-in | typed_answer | Exact match required |
| Quizlet | Term/Definition | basic | Direct mapping |
| Quizlet | Multiple Choice | multiple_choice | If choices detected |
| Mnemosyne | Q&A | basic | Direct mapping |
| Mnemosyne | Cloze | cloze | {{}} syntax preserved |

#### Export Mapping

| Our Type | Target Format | Result | Data Loss |
|----------|---------------|--------|-----------|
| basic | Anki Basic | Perfect | None |
| multiple_choice | Anki MC | Perfect | None |
| cloze | Anki Cloze | Perfect | None |
| image_occlusion | Anki IO | Perfect | None |
| multiple_choice | Quizlet | Basic Q&A | Choices in question |
| cloze | Quizlet | Basic Q&A | Blanks filled |

### Media File Handling

#### Supported Media Types

| Type | Extensions | Import | Export | Notes |
|------|------------|--------|--------|-------|
| Images | .jpg, .png, .gif, .webp | ✅ | ✅ | Anki, JSON |
| Audio | .mp3, .wav, .ogg | ✅ | ✅ | Anki only |
| Video | .mp4, .webm | ✅ | ✅ | Anki only |

#### Media Processing

**Import Process:**
1. Extract media from package
2. Store in our media storage
3. Update file references
4. Generate thumbnails (images)

**Export Process:**
1. Collect referenced media
2. Copy to export package
3. Update references for target format
4. Generate media manifest

### Tag and Metadata Mapping

#### Tag Normalization

**Input Variations:**
- Comma-separated: `"math, algebra, equations"`
- Semicolon-separated: `"math; algebra; equations"`
- Space-separated: `"math algebra equations"`
- Array format: `["math", "algebra", "equations"]`

**Our Standard:**
```json
{
    "tags": ["math", "algebra", "equations"]
}
```

**Tag Cleaning:**
- Convert to lowercase
- Remove special characters
- Trim whitespace
- Remove duplicates
- Validate length

#### Metadata Preservation

**Import Metadata:**
```json
{
    "import_source": "anki",
    "original_deck": "Biology Chapter 1",
    "import_date": "2025-09-09T10:00:00Z",
    "original_id": "anki_card_123"
}
```

**Export Metadata:**
```json
{
    "export_date": "2025-09-09T10:00:00Z",
    "export_format": "anki",
    "total_cards": 150,
    "source_unit": "Math Unit 2",
    "version": "1.0"
}
```

## Best Practices

### Before Importing

**Data Preparation:**
1. **Clean your source data**
   - Fix typos and formatting
   - Standardize tag usage
   - Remove duplicate cards
   - Verify media links

2. **Test with small sample**
   - Import 5-10 cards first
   - Verify card types are correct
   - Check media file handling
   - Test review functionality

3. **Backup existing data**
   - Export current cards
   - Document import settings
   - Save original files

### During Import

**Best Practices:**
1. **Use preview feature**
   - Always preview before importing
   - Check for validation errors
   - Verify card type detection
   - Review duplicate matches

2. **Handle duplicates wisely**
   - **Skip**: Ignore duplicate cards
   - **Update**: Replace existing cards
   - **Create**: Allow duplicates with different IDs
   - **Merge**: Combine card data

3. **Monitor progress**
   - Watch for error messages
   - Note skipped/failed cards
   - Check import statistics

### After Import

**Verification Steps:**
1. **Review imported cards**
   - Check random samples
   - Verify card types work correctly
   - Test media file display
   - Confirm tags and metadata

2. **Test review functionality**
   - Start a review session
   - Test different card types
   - Verify answer validation
   - Check spacing algorithm

3. **Document import details**
   - Save import settings used
   - Note any data transformations
   - Record success/failure rates

### Before Exporting

**Preparation:**
1. **Review card selection**
   - Filter by quality/accuracy
   - Check for empty/invalid cards
   - Verify media file availability
   - Test card functionality

2. **Clean up data**
   - Fix validation errors
   - Standardize formatting
   - Update outdated content
   - Remove test cards

### During Export

**Best Practices:**
1. **Choose appropriate format**
   - Match target platform capabilities
   - Consider media file support
   - Plan for data loss/transformation
   - Test import on target platform

2. **Configure options carefully**
   - Include/exclude media files
   - Select metadata fields
   - Choose card type mapping
   - Set file naming conventions

### After Export

**Verification:**
1. **Test import on target platform**
   - Import a small sample first
   - Verify all card types work
   - Check media file display
   - Test platform-specific features

2. **Document export process**
   - Save export settings
   - Note any data transformations
   - Record platform compatibility
   - Share with other users

## Troubleshooting

### Common Import Problems

#### "File format not recognized"

**Causes:**
- Unsupported file extension
- Corrupted file
- Wrong MIME type
- File too large

**Solutions:**
1. Check supported formats list
2. Try different file format
3. Re-download/re-export from source
4. Split large files into smaller chunks

**Example Fix:**
```bash
# Convert text file to proper CSV
sed 's/\t/,/g' quizlet_export.txt > quizlet_export.csv
```

#### "Delimiter detection failed"

**Causes:**
- Inconsistent formatting
- Mixed delimiters
- Special characters
- Encoding issues

**Solutions:**
1. **Manual delimiter specification:**
   ```json
   {
       "import_method": "paste",
       "content": "Question 1\tAnswer 1\nQuestion 2\tAnswer 2",
       "format_options": {
           "delimiter": "tab",
           "force_detection": false
       }
   }
   ```

2. **Clean data before import:**
   ```python
   # Python script to clean data
   import csv
   
   with open('messy_data.txt', 'r') as infile:
       content = infile.read()
       # Replace mixed delimiters with tabs
       content = content.replace(' - ', '\t')
       content = content.replace(',', '\t')
   
   with open('clean_data.txt', 'w') as outfile:
       outfile.write(content)
   ```

#### "Validation errors during import"

**Common Errors:**

1. **Missing required fields:**
   ```
   Error: Row 5: Question field is required
   Solution: Add question text or remove row
   ```

2. **Invalid card type data:**
   ```
   Error: Row 3: Multiple choice card needs at least 2 choices
   Solution: Add more choices or change card type
   ```

3. **Invalid characters:**
   ```
   Error: Row 7: Question contains invalid HTML tags
   Solution: Remove <script> tags or encode properly
   ```

**Bulk Validation Script:**
```javascript
// JavaScript validation helper
function validateFlashcards(cards) {
    const errors = [];
    
    cards.forEach((card, index) => {
        if (!card.question || card.question.trim() === '') {
            errors.push(`Row ${index + 1}: Question is required`);
        }
        
        if (card.card_type === 'multiple_choice') {
            if (!card.choices || card.choices.length < 2) {
                errors.push(`Row ${index + 1}: Multiple choice needs ≥2 choices`);
            }
            if (!card.correct_choices || card.correct_choices.length === 0) {
                errors.push(`Row ${index + 1}: Must specify correct choices`);
            }
        }
    });
    
    return errors;
}
```

#### "Media files not found"

**Causes:**
- Broken file paths
- Missing media folder
- Unsupported file types
- File size too large

**Solutions:**
1. **Re-export with media included**
2. **Manual media upload:**
   ```bash
   # Upload media to storage
   curl -X POST /api/media/upload \
        -F "file=@image.jpg" \
        -F "type=flashcard_media"
   ```
3. **Update file references:**
   ```json
   {
       "question_image_url": "/storage/flashcard_media/image_123.jpg"
   }
   ```

### Common Export Problems

#### "Export file is corrupted"

**Causes:**
- Large file size
- Memory limitations
- Network interruption
- Special characters

**Solutions:**
1. **Reduce export size:**
   ```javascript
   // Export in smaller batches
   const batchSize = 100;
   for (let i = 0; i < totalCards; i += batchSize) {
       const batch = cards.slice(i, i + batchSize);
       exportBatch(batch, i / batchSize);
   }
   ```

2. **Use streaming export:**
   ```php
   // Stream large exports
   return response()->streamDownload(function () use ($flashcards) {
       $stream = fopen('php://output', 'w');
       foreach ($flashcards as $card) {
           fputcsv($stream, $card->toArray());
       }
       fclose($stream);
   }, 'flashcards.csv');
   ```

#### "Target platform rejects import"

**Common Issues:**

1. **Format incompatibility:**
   ```
   Issue: Anki doesn't recognize card type
   Solution: Use basic card type for complex cards
   ```

2. **Character encoding:**
   ```
   Issue: Special characters show as ???
   Solution: Ensure UTF-8 encoding
   ```

3. **File size limits:**
   ```
   Issue: Platform has 10MB limit
   Solution: Export without media or split files
   ```

**Platform-Specific Fixes:**

**Quizlet:**
```python
# Convert complex cards to Quizlet format
def simplify_for_quizlet(card):
    if card['type'] == 'multiple_choice':
        question = card['question']
        choices = card['choices']
        correct = card['correct_choices'][0]
        
        # Add choices to question
        for i, choice in enumerate(choices):
            question += f"\n{chr(65+i)}) {choice}"
        
        return {
            'question': question,
            'answer': f"{chr(65+correct)}) {choices[correct]}"
        }
    
    return card
```

**Anki:**
```javascript
// Ensure proper note type mapping
function mapToAnkiNoteType(card) {
    const mapping = {
        'basic': 'Basic',
        'multiple_choice': 'Basic',  // Convert to basic
        'cloze': 'Cloze',
        'typed_answer': 'Basic (type in the answer)'
    };
    
    return mapping[card.type] || 'Basic';
}
```

### Performance Issues

#### "Import/Export is too slow"

**Optimization Strategies:**

1. **Batch processing:**
   ```php
   // Process in chunks
   $flashcards = collect($data)->chunk(100);
   
   foreach ($flashcards as $chunk) {
       DB::transaction(function () use ($chunk) {
           Flashcard::insert($chunk->toArray());
       });
   }
   ```

2. **Background processing:**
   ```php
   // Queue large operations
   ProcessFlashcardImport::dispatch($unitId, $importData);
   ```

3. **Progress tracking:**
   ```javascript
   // Show progress during import
   async function importWithProgress(data) {
       const total = data.length;
       let processed = 0;
       
       for (const chunk of chunkArray(data, 50)) {
           await importChunk(chunk);
           processed += chunk.length;
           updateProgress(processed / total * 100);
       }
   }
   ```

#### "Memory errors during processing"

**Solutions:**

1. **Stream processing:**
   ```php
   // Stream large files
   $handle = fopen($file, 'r');
   while (($row = fgetcsv($handle)) !== false) {
       processRow($row);
       if (memory_get_usage() > 100 * 1024 * 1024) {
           gc_collect_cycles();
       }
   }
   fclose($handle);
   ```

2. **Database chunking:**
   ```php
   // Process records in chunks
   Flashcard::where('unit_id', $unitId)
       ->chunk(200, function ($flashcards) {
           foreach ($flashcards as $flashcard) {
               processFlashcard($flashcard);
           }
       });
   ```

## Migration Strategies

### Platform Migration

#### From Quizlet to Our System

**Steps:**
1. Export from Quizlet as text file
2. Use our Quizlet import format
3. Review and enhance imported cards
4. Add card type variations
5. Include media files

**Enhancement Opportunities:**
- Convert basic cards to multiple choice
- Add cloze deletion variants
- Include images and audio
- Organize with better tagging

#### From Anki to Our System

**Steps:**
1. Export Anki deck as .apkg
2. Import with full feature preservation
3. Review card scheduling
4. Test all card types
5. Verify media files

**Considerations:**
- Anki intervals vs. our algorithm
- Custom note types handling
- Plugin-specific features
- Shared deck compatibility

#### From Our System to Other Platforms

**Anki Migration:**
1. Export as Anki package
2. Test import in Anki
3. Verify card types and media
4. Document any limitations

**Quizlet Migration:**
1. Export as Quizlet text format
2. Note data simplification
3. Manually add images if needed
4. Test in Quizlet platform

### Data Backup Strategies

#### Regular Exports

**Automated Backup:**
```bash
#!/bin/bash
# Daily backup script
DATE=$(date +%Y%m%d)
curl -X POST https://app.com/api/units/all/export \
     -H "Authorization: Bearer $TOKEN" \
     -d '{"format": "json"}' \
     -o "backup_$DATE.json"
```

**Version Control:**
```bash
# Git-based versioning
git add backups/
git commit -m "Daily flashcard backup - $(date)"
git push origin main
```

#### Incremental Backups

**Track Changes:**
```sql
-- Export only changed cards
SELECT * FROM flashcards 
WHERE updated_at > '2025-09-08 00:00:00'
ORDER BY updated_at;
```

**Differential Export:**
```javascript
// Export only modified cards
const lastBackup = new Date('2025-09-08');
const changedCards = await getFlashcards({
    filter: { updated_since: lastBackup }
});
exportCards(changedCards, 'incremental_backup.json');
```

This comprehensive import/export guide covers all supported formats, data mapping strategies, best practices, and troubleshooting scenarios. It serves as a complete reference for users migrating data between platforms and managing their flashcard collections effectively.