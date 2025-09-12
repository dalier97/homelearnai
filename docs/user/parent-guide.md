# Flashcard System - Parent User Guide

## Table of Contents
1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Creating Flashcards](#creating-flashcards)
4. [Card Types](#card-types)
5. [Import System](#import-system)
6. [Review System](#review-system)
7. [Print System](#print-system)
8. [Export System](#export-system)
9. [Advanced Features](#advanced-features)
10. [Best Practices](#best-practices)
11. [Troubleshooting](#troubleshooting)

## Overview

The Flashcard System is a comprehensive tool designed to enhance your homeschool learning experience. It supports multiple card types, seamless import/export with popular platforms like Quizlet and Anki, and integrates with the spaced repetition review system to optimize learning retention.

### Key Features
- **Multiple Card Types**: Basic Q&A, Multiple Choice, True/False, Cloze Deletion, Typed Answer, and Image Occlusion
- **Universal Import**: Import from Quizlet, Anki, Mnemosyne, CSV, and more
- **Smart Review System**: Integrated spaced repetition with adaptive scheduling
- **Print Ready**: Generate PDFs in various layouts for offline study
- **Export Anywhere**: Export to all major flashcard platforms
- **Performance Optimized**: Fast search, caching, and bulk operations
- **Kids Mode Compatible**: Age-appropriate interface and safety features

## Getting Started

### Accessing Flashcards

1. Navigate to any Unit in your curriculum
2. Scroll to the "Flashcards" section
3. Click "Add Flashcard" to create your first card
4. Or click "Import Flashcards" to bulk import from external sources

### Quick Start Checklist
- [ ] Create your first basic flashcard
- [ ] Try importing sample data from Quizlet
- [ ] Review flashcards in the review system
- [ ] Print a few cards to test offline study
- [ ] Export and share with other platforms

## Creating Flashcards

### Basic Flashcard Creation

1. **Click "Add Flashcard"** in any unit
2. **Choose Card Type** from the dropdown
3. **Fill Required Fields** based on card type
4. **Set Difficulty Level** (Easy/Medium/Hard)
5. **Add Tags** (optional) for organization
6. **Save** to add to your collection

### Quick Tips
- Use clear, concise questions
- Keep answers specific and accurate
- Add hints for kids mode
- Use tags to organize by topic
- Set appropriate difficulty levels

## Card Types

### 1. Basic Cards (Traditional Q&A)
**Best for**: Definitions, facts, simple concepts

**Example**:
- Question: "What is the capital of France?"
- Answer: "Paris"
- Hint: "City of lights"

### 2. Multiple Choice
**Best for**: Testing recognition, concept identification

**Example**:
- Question: "Which are prime numbers?"
- Options: ["2", "4", "5", "9"]
- Correct: [0, 2] (2 and 5)

**Tips**:
- Use 2-6 answer options
- Support single or multiple correct answers
- Make distractors plausible but clearly wrong

### 3. True/False
**Best for**: Quick concept verification, fact checking

**Example**:
- Question: "The Earth is flat"
- Answer: "False"
- Hint: "Think about satellite images"

### 4. Cloze Deletion (Fill-in-the-blank)
**Best for**: Context-based learning, sentence completion

**Example**:
- Text: "The {{mitochondria}} is the {{powerhouse}} of the cell"
- Answers: ["mitochondria", "powerhouse"]

**Syntax**: Use `{{word}}` to mark blanks

### 5. Typed Answer
**Best for**: Spelling, exact recall, precise answers

**Example**:
- Question: "Spell the word for a large African animal with a trunk"
- Answer: "elephant"
- Validation: Exact match required

### 6. Image Occlusion
**Best for**: Anatomy, diagrams, visual learning

**Example**:
- Upload diagram image
- Mark areas to hide with coordinates
- Students identify hidden parts

## Import System

### Supported Formats

#### Quizlet Import
**Most Popular** - Works with Quizlet exports and copy-paste

**Format Options**:
```
Term[TAB]Definition
Term,Definition  
Term - Definition
```

**Steps**:
1. Export from Quizlet or copy study set
2. Paste into import dialog
3. Auto-detection handles formatting
4. Preview before importing

#### Anki Package (.apkg)
**Full Featured** - Preserves all card types and media

**Steps**:
1. Export deck from Anki
2. Upload .apkg file
3. Maps note types automatically
4. Imports media files

#### CSV Import
**Universal** - Works with any spreadsheet

**Format**:
```csv
Type,Question,Answer,Choices,Correct,Hint,Tags
basic,"What is 2+2?","4",,,"","math"
multiple_choice,"Pick even","","2;3;4;5","0;2","","math"
```

#### Other Formats
- **Mnemosyne XML** (.mem)
- **SuperMemo** (Q&A format)
- **JSON** (our native format)

### Import Process

1. **Choose Import Method**
   - File Upload
   - Copy/Paste Text
   - URL Import (for supported platforms)

2. **Auto-Detection**
   - Format recognition
   - Delimiter detection
   - Card type identification

3. **Preview & Validation**
   - See parsed cards
   - Fix validation errors
   - Adjust import settings

4. **Execute Import**
   - Bulk creation
   - Duplicate detection
   - Error reporting

### Import Tips
- Start with small test imports
- Clean up data in spreadsheet first
- Use consistent formatting
- Check preview carefully before importing
- Keep backups of original data

## Review System

### How Reviews Work

Flashcards automatically integrate with the spaced repetition system:

1. **Initial Learning**: New cards appear more frequently
2. **Spaced Intervals**: Successful reviews increase intervals
3. **Adaptive Scheduling**: Difficulty affects next review date
4. **Mixed Content**: Flashcards mix with other review items

### Review Interface by Card Type

#### Basic Cards
- Show question first
- Click "Show Answer" to reveal
- Rate difficulty (Again/Hard/Good/Easy)

#### Multiple Choice
- Display question with options
- Select answer(s)
- Immediate feedback on correctness
- Rate based on confidence

#### True/False
- Single question with T/F options
- Quick assessment format
- Good for rapid review sessions

#### Cloze Deletion
- Shows text with blanks
- Type answers in order
- Partial credit for close answers
- Validates each blank separately

#### Typed Answer
- Requires exact spelling
- Case-insensitive by default
- Shows correct answer if wrong
- Great for vocabulary building

#### Image Occlusion
- Shows image with hidden areas
- Click to reveal specific parts
- Progressive disclosure available
- Visual memory reinforcement

### Review Strategies

**For Parents**:
- Review with younger children
- Use hint system in kids mode
- Set appropriate session lengths
- Monitor progress regularly

**For Independent Learners**:
- Focus on difficult cards
- Use timer for motivation
- Track accuracy statistics
- Adjust difficulty levels

## Print System

### Print Layouts

#### Index Cards (3x5 or 4x6)
**Best for**: Traditional flashcard study
- Question on front, answer on back
- Perforated cutting lines
- Standard card dimensions

#### Foldable Cards
**Best for**: Self-study, portability
- Both sides visible when folded
- 2 cards per page
- Easy to create and use

#### Grid Layout
**Best for**: Quick reference, overview
- 6 cards per page
- Question and answer visible
- Compact format

#### Study Sheet
**Best for**: Review, testing preparation
- List format with questions
- Answers right-aligned
- Great for final review

### Print Process

1. **Select Cards**
   - Choose specific cards
   - Filter by difficulty/type
   - Select entire unit

2. **Choose Layout**
   - Pick format based on use case
   - Preview before printing
   - Adjust settings if needed

3. **Generate PDF**
   - High-quality output
   - Print-ready formatting
   - Download immediately

### Print Tips
- Use cardstock for index cards
- Print duplex for two-sided cards
- Check cutting guidelines
- Test print one page first
- Consider laminating for durability

## Export System

### Export Formats

#### Anki Package
**Features**: Full compatibility, preserves everything
**Use Case**: Moving to Anki for advanced features

#### Quizlet Format
**Features**: Basic cards only, ready to import
**Use Case**: Sharing with Quizlet users

#### CSV/Excel
**Features**: Editable in spreadsheet
**Use Case**: Data manipulation, analysis

#### JSON Backup
**Features**: Complete data preservation
**Use Case**: System backups, migration

#### PDF Document
**Features**: Formatted for printing/sharing
**Use Case**: Offline study materials

### Export Process

1. **Select Export Format**
2. **Choose Cards to Export**
   - All cards in unit
   - Filtered selection
   - Specific card types
3. **Configure Options**
   - Include media files
   - Preserve formatting
   - Set metadata options
4. **Download Export File**

### Export Tips
- Test imports in target platform
- Keep original data as backup
- Document export settings used
- Share with appropriate permissions

## Advanced Features

### Search and Filtering

**Quick Search**:
- Search questions, answers, hints
- Real-time results
- Keyboard shortcuts

**Advanced Filters**:
- By card type
- By difficulty level
- By creation date
- By tag
- By performance metrics

### Bulk Operations

**Bulk Status Update**:
- Activate/deactivate multiple cards
- Change difficulty levels
- Add tags to groups

**Bulk Import/Export**:
- Handle large datasets
- Progress indicators
- Error reporting

### Performance Optimization

**Caching System**:
- Fast loading of large collections
- Background cache warming
- Automatic invalidation

**Search Performance**:
- Indexed full-text search
- Suggestion system
- Recent search history

### Duplicate Detection

**Smart Detection**:
- Fuzzy matching algorithms
- Cross-format compatibility
- Merge strategies

**Resolution Options**:
- Skip duplicates
- Merge similar cards
- Replace existing cards
- Create variations

## Best Practices

### Content Creation

**Question Writing**:
- Be specific and unambiguous
- Use proper grammar and spelling
- Test questions with target audience
- Include context when necessary

**Answer Formatting**:
- Keep answers concise
- Use consistent formatting
- Include alternative acceptable answers
- Verify accuracy thoroughly

**Difficulty Assignment**:
- Easy: Basic recall, recognition
- Medium: Application, comprehension
- Hard: Analysis, synthesis, evaluation

### Organization

**Tagging Strategy**:
- Use consistent tag vocabulary
- Create hierarchical tags (math.algebra.equations)
- Tag by topic, difficulty, source
- Regularly clean up unused tags

**Unit Organization**:
- Group related concepts together
- Sequence from basic to advanced
- Balance card types within units
- Regular review and reorganization

### Learning Optimization

**Review Scheduling**:
- Daily sessions for new material
- Weekly review of difficult cards
- Monthly comprehensive reviews
- Seasonal curriculum review

**Mixed Practice**:
- Combine different card types
- Mix easy and difficult cards
- Include related concepts
- Regular format variation

### Data Management

**Backup Strategy**:
- Regular JSON exports
- Version control for major changes
- Cloud storage for media files
- Document import sources

**Quality Control**:
- Regular accuracy reviews
- Student feedback integration
- Performance metric monitoring
- Continuous improvement

## Troubleshooting

### Common Issues

#### Import Problems

**"File format not recognized"**
- Check file extension
- Verify file isn't corrupted
- Try different format
- Contact support with sample

**"Delimiter detection failed"**
- Specify delimiter manually
- Check for consistent formatting
- Remove special characters
- Use CSV format instead

**"Validation errors during import"**
- Check required fields
- Verify data types
- Review preview carefully
- Fix source data first

#### Review Issues

**"Cards not appearing in reviews"**
- Check if cards are active
- Verify unit is assigned to child
- Ensure review scheduling is enabled
- Check difficulty filters

**"Performance is slow"**
- Clear cache and refresh
- Reduce number of active cards
- Check internet connection
- Contact support if persistent

#### Print/Export Problems

**"PDF generation fails"**
- Try smaller card selection
- Check for special characters
- Use simpler layout
- Report bug with details

**"Export file is corrupted"**
- Try different format
- Reduce export size
- Check available disk space
- Use download manager

### Getting Help

**Self-Help Resources**:
- Check FAQ section
- Review video tutorials
- Consult troubleshooting guide
- Search documentation

**Community Support**:
- User forums
- Facebook groups
- Discord channel
- Local homeschool groups

**Technical Support**:
- Email: support@learningapp.com
- Include error screenshots
- Describe steps to reproduce
- Provide system information

## Conclusion

The Flashcard System is designed to be powerful yet easy to use. Start with basic cards and gradually explore advanced features as you become comfortable. Remember that the key to effective flashcard use is consistency - regular creation, review, and refinement of your cards will yield the best learning outcomes.

For additional help:
- Check the [Kids Guide](kids-guide.md) for age-appropriate instructions
- Review [Import/Export Guide](../guides/import-export.md) for detailed format information
- Consult [API Documentation](../technical/api-documentation.md) for developers
- Visit [FAQ](../faq.md) for quick answers

Happy learning!