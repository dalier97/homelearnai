# Flashcard System - FAQ & Troubleshooting

## Table of Contents
1. [Frequently Asked Questions](#frequently-asked-questions)
2. [Getting Started](#getting-started)
3. [Import/Export Issues](#importexport-issues)
4. [Review System](#review-system)
5. [Print & PDF Problems](#print--pdf-problems)
6. [Performance Issues](#performance-issues)
7. [Browser Compatibility](#browser-compatibility)
8. [Mobile Devices](#mobile-devices)
9. [Data and Privacy](#data-and-privacy)
10. [Technical Support](#technical-support)

## Frequently Asked Questions

### General Questions

**Q: What makes this flashcard system different from Quizlet or Anki?**

A: Our system is specifically designed for homeschool families with these unique features:
- **Integrated Learning**: Seamlessly works with your curriculum planning
- **Family-Friendly**: Kids mode with age-appropriate interfaces
- **Universal Import**: Import from any platform (Quizlet, Anki, Mnemosyne)
- **Intelligent Reviews**: Spaced repetition integrated with other learning activities
- **Offline Study**: High-quality PDF printing in multiple layouts
- **Complete Control**: Your data stays private, no platform lock-in

**Q: How many flashcards can I create?**

A: There are no artificial limits! The system is designed to handle:
- Unlimited flashcards per unit
- Thousands of cards with fast performance
- Large imports (500+ cards in one operation)
- Efficient storage and quick searching

**Q: Can I use this offline?**

A: Partial offline support:
- **Print to PDF**: Create offline study materials
- **Download exports**: Keep backups locally
- **Mobile browser**: Some caching for recent cards
- **Full offline mode**: Planned for future updates

**Q: Is my data safe and private?**

A: Yes! Your flashcard data is:
- Stored securely on our servers
- Never shared with third parties
- Backed up daily with encryption
- Exportable anytime (no lock-in)
- Subject to our privacy policy

### Card Types and Features

**Q: What card types are supported?**

A: We support 6 card types:
1. **Basic**: Traditional question/answer
2. **Multiple Choice**: Single or multiple correct answers
3. **True/False**: Quick assessment format
4. **Cloze Deletion**: Fill-in-the-blanks with {{syntax}}
5. **Typed Answer**: Requires exact spelling
6. **Image Occlusion**: Hide parts of images (planned)

**Q: Can I add images to flashcards?**

A: Yes! You can:
- Upload images for questions and answers
- Import images from Anki packages
- Use images in PDF exports
- Support for JPG, PNG, GIF, WebP formats

**Q: How do tags work?**

A: Tags help organize your flashcards:
- Add multiple tags to any card
- Search and filter by tags
- Auto-suggest existing tags
- Import tags from other platforms
- Use hierarchical tags (math.algebra.equations)

### Kids Mode and Safety

**Q: What is Kids Mode?**

A: Kids Mode is a simplified, safe interface that:
- Prevents accidental deletion or editing
- Shows age-appropriate language and colors
- Hides complex features and settings
- Provides extra encouragement and hints
- Tracks progress without pressure

**Q: How do I enable/disable Kids Mode?**

A: Parents can toggle Kids Mode:
1. Click your profile icon
2. Select "Switch to Kids Mode" 
3. Enter your PIN when prompted
4. To exit, click "Parent Mode" and enter PIN

**Q: Can kids create their own flashcards?**

A: In Kids Mode, children can:
- Study existing flashcards
- Mark cards as easy/hard
- Use hints and help features
- **Cannot**: Create, edit, or delete cards

## Getting Started

### Setting Up Your First Flashcards

**Q: How do I create my first flashcard?**

A: Follow these steps:
1. Navigate to any Unit in your curriculum
2. Scroll to the "Flashcards" section
3. Click "Add Flashcard"
4. Choose card type (start with "Basic")
5. Fill in question and answer
6. Set difficulty level
7. Add tags (optional)
8. Click "Save"

**Q: Should I start by creating cards or importing them?**

A: We recommend:
- **Start small**: Create 5-10 cards manually to understand the system
- **Then import**: Use existing collections from Quizlet or Anki
- **Enhance imported cards**: Add images, hints, and different card types
- **Organize**: Use tags and difficulty levels

**Q: What's the best way to organize flashcards?**

A: Best practices:
- **One unit per topic**: Keep related cards together
- **Use consistent tags**: Create a tag vocabulary
- **Set appropriate difficulty**: Be honest about complexity
- **Include hints**: Especially for kids mode
- **Regular review**: Update outdated cards

### Import/Export Issues

**Q: Why can't I import my Quizlet cards?**

A: Common solutions:

1. **Check the format**:
   ```
   Correct: Question[TAB]Answer
   Incorrect: Question - Answer (without tab)
   ```

2. **Copy correctly from Quizlet**:
   - Go to your Quizlet set
   - Click "Export" → "Copy text"
   - Paste directly into our import box

3. **Clean up the data**:
   - Remove blank lines
   - Fix formatting inconsistencies
   - Check for special characters

**Q: My Anki import failed. What should I do?**

A: Troubleshooting steps:

1. **Check file size**: Large packages (>50MB) may timeout
   - Export smaller decks from Anki
   - Remove unnecessary media files

2. **Verify .apkg format**: 
   - Re-export from Anki
   - Include media files option
   - Use "Anki Deck Package" not "Collection"

3. **Try with sample deck**:
   - Import a small test deck first
   - Verify the process works
   - Then try your full deck

**Q: How do I export flashcards to use in other apps?**

A: Export process:
1. Go to your unit with flashcards
2. Click "Export Flashcards"
3. Choose your target format:
   - **Anki**: Full feature preservation
   - **Quizlet**: Basic cards only
   - **CSV**: For spreadsheet editing
   - **PDF**: For printing

**Q: Why doesn't my export work in [other platform]?**

A: Platform compatibility:

**Quizlet**: 
- Only accepts basic Q&A format
- No multiple choice or cloze cards
- Remove media files before export

**Anki**:
- Should work perfectly
- If issues, try importing into new Anki profile first

**Other platforms**:
- Use CSV format for maximum compatibility
- May need manual conversion

## Review System

**Q: How does the spaced repetition work?**

A: Our algorithm:
- **New cards**: Appear frequently until learned
- **Easy cards**: Longer intervals between reviews
- **Hard cards**: Shorter intervals, more practice
- **Integrated**: Works with other review items in sessions

**Q: Why aren't my flashcards appearing in reviews?**

A: Check these settings:
1. **Cards are active**: Inactive cards don't appear
2. **Unit is assigned**: Make sure unit is assigned to the child
3. **Review scheduling**: Ensure reviews are enabled for the unit
4. **Review filters**: Check if card type filters are applied

**Q: Can I control how often cards appear?**

A: Yes, several ways:
- **Difficulty level**: Easy cards appear less often
- **Manual rating**: Rate cards after each review
- **Card status**: Deactivate cards you've mastered
- **Bulk operations**: Adjust multiple cards at once

**Q: How do different card types work in reviews?**

A: Each type has specific behavior:

- **Basic**: Show question → Show answer → Rate difficulty
- **Multiple Choice**: Show options → Select → Immediate feedback
- **True/False**: Quick T/F selection → Immediate feedback  
- **Cloze**: Show blanks → Type answers → Check each blank
- **Typed Answer**: Exact spelling required → Immediate feedback

## Print & PDF Problems

**Q: Why is my PDF generation failing?**

A: Common causes and solutions:

1. **Too many cards selected**:
   - Try smaller batches (50-100 cards)
   - Use pagination for large sets

2. **Special characters in text**:
   - Check for unusual symbols
   - Remove emojis if present
   - Try different card selection

3. **Browser/device limitations**:
   - Use desktop browser for large PDFs
   - Ensure sufficient RAM available
   - Try incognito/private mode

**Q: The PDF layout doesn't look right. How do I fix it?**

A: Layout troubleshooting:

1. **Choose appropriate layout**:
   - **Index Cards**: For traditional flashcard study
   - **Foldable**: For portable study sheets
   - **Grid**: For quick reference
   - **Study Sheet**: For test preparation

2. **Check your content**:
   - Very long questions may wrap poorly
   - Images may affect spacing
   - Try preview mode first

3. **Print settings**:
   - Use "Actual Size" not "Fit to Page"
   - Check margins in print dialog
   - Consider duplex printing for two-sided cards

**Q: Can I customize the PDF layouts?**

A: Current customization options:
- Choose from 4 different layouts
- Include/exclude hints
- Select paper size (Letter, A4)
- Control card selection

Advanced customization is planned for future updates.

## Performance Issues

**Q: The app is running slowly. How can I speed it up?**

A: Performance optimization:

1. **Clear cache**:
   - Refresh the page (Ctrl+F5 or Cmd+Shift+R)
   - Clear browser cache
   - Log out and back in

2. **Reduce load**:
   - Close other browser tabs
   - Limit active flashcards (under 1000 per unit)
   - Use search/filters instead of loading all cards

3. **Check connection**:
   - Ensure stable internet
   - Try different network if possible
   - Restart router if needed

**Q: Import/export is taking too long. What can I do?**

A: Speed up operations:

1. **Break up large imports**:
   - Import in batches of 100-200 cards
   - Use CSV format for fastest processing
   - Remove media files for speed

2. **Background processing**:
   - Large operations run in background
   - You'll get notification when complete
   - Don't close browser during processing

3. **Optimize your data**:
   - Remove blank/duplicate cards
   - Simplify complex formatting
   - Use consistent tag format

**Q: Why are searches slow?**

A: Search optimization:
- Use specific terms rather than single letters
- Search within card types or difficulty levels
- Use tags for faster filtering
- Index rebuilding may be needed (contact support)

## Browser Compatibility

**Q: Which browsers are supported?**

A: Fully supported browsers:
- **Chrome**: Latest 2 versions ✅
- **Firefox**: Latest 2 versions ✅  
- **Safari**: Latest 2 versions ✅
- **Edge**: Latest 2 versions ✅

Limited support:
- **Internet Explorer**: Not supported ❌
- **Older browsers**: May have issues ⚠️

**Q: I'm having issues with [specific browser]. What should I do?**

A: Browser-specific fixes:

**Chrome**:
- Update to latest version
- Disable ad blockers temporarily
- Clear site data (Settings → Privacy → Site data)

**Firefox**:
- Check Enhanced Tracking Protection settings
- Disable strict mode temporarily
- Clear cookies and cache

**Safari**:
- Enable JavaScript if disabled
- Check cross-site tracking settings
- Update to latest macOS/iOS version

**Q: Do I need to install anything?**

A: No installation required:
- Works entirely in your web browser
- No plugins or extensions needed
- No downloads except for exports/PDFs
- Works on any device with modern browser

## Mobile Devices

**Q: Does the flashcard system work on phones and tablets?**

A: Yes! Mobile features:
- **Responsive design**: Adapts to screen size
- **Touch-friendly**: Large buttons and gestures
- **Offline PDFs**: Download for offline study
- **Fast loading**: Optimized for mobile networks

**Q: Is there a mobile app?**

A: Currently web-based only, but:
- Works great in mobile browsers
- Add to home screen for app-like experience
- Push notifications planned
- Native app under consideration

**Q: How do I use flashcards on my phone?**

A: Mobile best practices:
1. **Add to home screen**: Tap share button → "Add to Home Screen"
2. **Use landscape mode**: Better for reading cards
3. **Enable push notifications**: Get review reminders
4. **Download PDFs**: For offline study

**Q: Touch gestures don't work properly. Help?**

A: Mobile troubleshooting:
1. **Update browser**: Use latest version
2. **Clear cache**: Fix touch response issues
3. **Check zoom level**: 100% zoom works best
4. **Restart browser**: Close and reopen
5. **Try different browser**: Test Chrome vs Safari

## Data and Privacy

**Q: Where is my flashcard data stored?**

A: Data storage details:
- **Primary**: Secure cloud servers (AWS/Google Cloud)
- **Backups**: Encrypted daily backups
- **Location**: US data centers
- **Access**: Only authorized personnel
- **Retention**: As long as your account is active

**Q: Can I download all my data?**

A: Yes! Data portability:
- Export individual units or entire collection
- JSON format preserves all data
- Include media files in exports
- No lock-in - take your data anywhere

**Q: What happens if I delete my account?**

A: Account deletion process:
1. **Grace period**: 30 days to recover
2. **Data removal**: All flashcards permanently deleted
3. **Exports**: Download data before deleting
4. **No recovery**: After 30 days, data cannot be restored

**Q: Do you share my data with other companies?**

A: No data sharing:
- Never sold to third parties
- Not used for advertising
- Anonymous analytics only (no personal data)
- Full privacy policy available
- COPPA compliant for children

## Technical Support

### Getting Help

**Q: How do I contact support?**

A: Support channels:
- **Email**: support@learningapp.com
- **Help Center**: Built-in help button
- **Community Forum**: User discussions
- **Video Tutorials**: Step-by-step guides

**Q: What information should I include in a support request?**

A: Helpful details:
- **What you were trying to do**
- **What happened instead**
- **Error messages** (exact text)
- **Browser and version**
- **Steps to reproduce**
- **Screenshots** if helpful

### Self-Help Resources

**Q: Where can I find video tutorials?**

A: Tutorial locations:
- **In-app help**: Click ? button anywhere
- **Getting Started guide**: After account creation
- **YouTube channel**: SearchYourLearningApp
- **Documentation**: docs.learningapp.com

**Q: Is there a user community?**

A: Community resources:
- **Facebook Group**: "Homeschool Learning App Users"
- **Discord Server**: Real-time chat support
- **Reddit**: r/HomeschoolLearningApp
- **Local groups**: Find users in your area

### Bug Reports

**Q: I found a bug. How do I report it?**

A: Bug reporting:
1. **Check known issues**: Review recent announcements
2. **Try to reproduce**: Can you make it happen again?
3. **Document steps**: Write down exactly what you did
4. **Include details**: Browser, device, error messages
5. **Send report**: Use bug report form or email

**Q: How quickly are bugs fixed?**

A: Bug priority levels:
- **Critical** (data loss, security): 24 hours
- **High** (major features broken): 1-3 days  
- **Medium** (minor features): 1-2 weeks
- **Low** (cosmetic, nice-to-have): Next release

Updates are released weekly with bug fixes and improvements.

### Feature Requests

**Q: Can I suggest new features?**

A: Absolutely! Feature requests:
- **Feedback form**: In-app suggestion box
- **Community voting**: Popular requests get priority
- **User interviews**: Detailed feedback sessions
- **Beta testing**: Try new features early

**Q: What new features are coming?**

A: Roadmap highlights:
- **Native mobile apps** (iOS/Android)
- **Advanced analytics** (learning insights)
- **Collaboration features** (family sharing)
- **AI-powered card generation**
- **Voice recording** for pronunciation

Join our newsletter for updates and early access opportunities!

---

## Still Need Help?

If you can't find the answer to your question here:

1. **Check the user guides**: More detailed instructions available
2. **Try the search**: Use Ctrl+F to search this page
3. **Contact support**: We're here to help!
4. **Join the community**: Other users often have great solutions

Remember: There are no silly questions! We want you to succeed with flashcards and are here to help make that happen.