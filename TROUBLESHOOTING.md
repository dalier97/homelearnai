# Homeschool Learning App - Troubleshooting Guide

This guide helps you diagnose and resolve common issues with the Homeschool Learning App.

## Quick Diagnostics

### Is the problem affecting:
- ✅ **Just me**: Clear your browser cache and cookies
- ✅ **Everyone**: Check system status and server connection
- ✅ **One child**: Check child-specific settings and data
- ✅ **One feature**: See feature-specific troubleshooting below

## Common Issues

### 1. Login and Authentication Problems

#### Cannot Login / "Access Denied"

**Symptoms**: 
- Login form rejects correct credentials
- Redirected back to login page
- "Invalid credentials" message

**Solutions**:
1. **Verify Credentials**:
   - Ensure caps lock is off
   - Try typing password in a text editor first
   - Check for extra spaces

2. **Clear Browser Data**:
   ```
   Chrome: Settings → Privacy → Clear browsing data
   Firefox: Settings → Privacy → Clear Data
   Safari: Safari → Clear History
   ```

3. **Try Different Browser**:
   - Test in incognito/private mode
   - Use different browser entirely

4. **Password Reset**:
   - Use "Forgot Password" link
   - Check email spam folder
   - Wait up to 10 minutes for email

#### Email Confirmation Issues

**Symptoms**:
- Registration successful but can't login
- Confirmation email not received
- Confirmation link doesn't work

**Solutions**:
1. **Check Email**:
   - Look in spam/junk folder
   - Add noreply@[yourdomain] to contacts
   - Wait up to 15 minutes

2. **Resend Confirmation**:
   - Try registering again (will resend)
   - Contact admin to manually confirm

3. **Email Confirmation Disabled**:
   - Admin may have disabled requirement
   - Try logging in directly

### 2. Dashboard and Display Issues

#### Data Not Loading / Blank Dashboard

**Symptoms**:
- Dashboard shows loading indefinitely
- Empty sections that should have data
- "No data found" when data exists

**Solutions**:
1. **Refresh Browser**:
   - Hard refresh: Ctrl+F5 (PC) or Cmd+Shift+R (Mac)
   - Clear cache and refresh

2. **Check Network**:
   - Ensure stable internet connection
   - Test other websites
   - Disable VPN if using one

3. **JavaScript Issues**:
   - Ensure JavaScript is enabled
   - Disable browser extensions temporarily
   - Check browser console for errors

#### Children Not Appearing

**Symptoms**:
- Added children don't show in dashboard
- Child selector is empty
- Planning board shows no children

**Solutions**:
1. **Verify Child Creation**:
   - Go to Children section
   - Ensure "Save" was clicked
   - Check for error messages

2. **Browser Refresh**:
   - Refresh the page
   - Try different browser tab

3. **Data Synchronization**:
   - Wait 30 seconds and refresh
   - Log out and back in

### 3. Planning and Scheduling Issues

#### Sessions Don't Appear in Calendar

**Symptoms**:
- Created sessions not visible in calendar
- Planning board shows sessions but calendar is empty
- Wrong day or time displayed

**Solutions**:
1. **Check Session Status**:
   - Sessions must be in "Scheduled" status
   - Move from Planned → Scheduled in Planning Board
   - Verify day of week is set

2. **Calendar Filters**:
   - Ensure correct child selected
   - Check if date range includes session dates
   - Verify time blocks exist for scheduled times

3. **Refresh Data**:
   - Refresh calendar page
   - Switch child and back
   - Clear browser cache

#### Cannot Move Sessions Between Days

**Symptoms**:
- Drag and drop not working
- "Access denied" when moving sessions
- Move options not available

**Solutions**:
1. **Check Independence Level**:
   - Level 1-2: Parent must do moves
   - Level 3+: Child can move within week
   - Level 4: Full flexibility

2. **Session Commitment Type**:
   - Fixed sessions cannot be moved
   - Check session details
   - Change commitment type if needed

3. **Browser Issues**:
   - Try different browser
   - Ensure JavaScript enabled
   - Disable extensions

### 4. Subject and Curriculum Problems

#### Cannot Create Units or Topics

**Symptoms**:
- "Save" button doesn't work
- Error messages about required fields
- Data not persisting

**Solutions**:
1. **Required Field Validation**:
   - Ensure all required fields filled
   - Name field is always required
   - Check for character limits

2. **Special Characters**:
   - Avoid special symbols in names
   - Use plain text for descriptions
   - Remove emoji if present

3. **Duplicate Names**:
   - Subject names must be unique
   - Unit names must be unique within subject
   - Topic names must be unique within unit

#### Progress Not Updating

**Symptoms**:
- Completed sessions don't update progress
- Unit completion percentage stuck
- Dashboard shows outdated progress

**Solutions**:
1. **Session Status**:
   - Ensure sessions marked as "completed" not just "done"
   - Check completion date is set
   - Verify session belongs to correct topic

2. **Cache Clear**:
   - Refresh browser
   - Clear cache and cookies
   - Wait 5-10 minutes for updates

### 5. Review System Issues

#### No Review Items Available

**Symptoms**:
- Review section shows "No reviews"
- Recently completed sessions not generating reviews
- Review counts show 0

**Solutions**:
1. **Review Slot Setup**:
   - Go to Reviews → Manage Review Slots
   - Add at least one active review slot
   - Ensure slot is not disabled

2. **Completed Sessions**:
   - Sessions must be marked "completed"
   - Wait 24-48 hours after completion
   - Check that topics have content to review

3. **Review Intervals**:
   - First review after 1-3 days
   - Subsequent reviews based on performance
   - Check review history for patterns

#### Review Session Won't Start

**Symptoms**:
- "Start Review" button not working
- Blank review interface
- Error messages in review session

**Solutions**:
1. **Browser Compatibility**:
   - Use supported browser (Chrome, Firefox, Safari)
   - Enable JavaScript
   - Disable ad blockers for the site

2. **Review Queue**:
   - Ensure reviews are actually due
   - Check review schedule
   - Verify child has completed sessions

### 6. Calendar Import Issues

#### ICS File Won't Import

**Symptoms**:
- File upload fails
- "Invalid format" error
- Import shows 0 events

**Solutions**:
1. **File Format**:
   - Ensure file has .ics extension
   - File size under 5MB
   - Export fresh copy from source

2. **File Content**:
   - Open file in text editor
   - Verify begins with "BEGIN:VCALENDAR"
   - Contains "BEGIN:VEVENT" sections

3. **Calendar Source**:
   - Google Calendar: Settings → Export
   - Outlook: File → Save Calendar
   - Apple: File → Export

#### URL Feed Import Fails

**Symptoms**:
- "Cannot fetch calendar" error
- URL import returns no events
- Authentication errors

**Solutions**:
1. **URL Accessibility**:
   - Test URL in browser
   - Ensure URL is publicly accessible
   - Check for authentication requirements

2. **URL Format**:
   - Use full URL including http/https
   - Ensure points to .ics file
   - Check for redirects or expiry

### 7. Performance Issues

#### App Loading Slowly

**Symptoms**:
- Pages take > 5 seconds to load
- Buttons slow to respond
- Timeouts or connection errors

**Solutions**:
1. **Network Issues**:
   - Test internet speed
   - Try different network/WiFi
   - Disable VPN temporarily

2. **Browser Optimization**:
   - Close unused tabs
   - Clear browser cache
   - Restart browser

3. **Device Performance**:
   - Close other applications
   - Restart device
   - Ensure sufficient RAM available

#### Data Not Syncing

**Symptoms**:
- Changes don't save
- Old data reappears after refresh
- Conflicts between devices

**Solutions**:
1. **Connection Stability**:
   - Ensure stable internet
   - Avoid switching networks during use
   - Wait for operations to complete

2. **Multiple Devices**:
   - Use only one device at a time
   - Refresh before making changes
   - Allow sync time between devices

## Browser-Specific Issues

### Chrome Issues
- **Enable third-party cookies** for authentication
- **Clear site data**: Settings → Privacy → Site Settings
- **Disable extensions** that block JavaScript
- **Update Chrome** to latest version

### Firefox Issues  
- **Enable JavaScript**: about:config → javascript.enabled
- **Clear cookies**: Preferences → Privacy → Clear Data
- **Disable tracking protection** for the site
- **Update Firefox** regularly

### Safari Issues
- **Enable JavaScript**: Safari → Preferences → Security
- **Clear website data**: Safari → Clear History
- **Disable content blockers** for the site
- **Update Safari** with system updates

### Mobile Browser Issues
- **Use latest iOS/Android** browser versions
- **Clear mobile browser cache** regularly
- **Ensure adequate storage** on device
- **Use WiFi** instead of cellular when possible

## Data Recovery

### Lost Sessions or Progress

**If you accidentally deleted data**:
1. **Check trash/recycle bin** (if available)
2. **Look in different status columns** (Backlog, Planned, etc.)
3. **Contact support** for data recovery assistance
4. **Restore from backup** if you created one

### Export Your Data

**Regular backups recommended**:
1. **Planning Data**: Export sessions from Planning Board
2. **Subject Data**: Copy subject/unit/topic information
3. **Progress Reports**: Save weekly/monthly summaries
4. **Calendar Data**: Export time blocks and schedules

## Getting Additional Help

### Before Contacting Support

1. **Try the solutions above** for your specific issue
2. **Test in different browser** to isolate the problem
3. **Note error messages** exactly as they appear
4. **Document steps** that led to the problem
5. **Check system requirements** are met

### What to Include in Support Requests

- **Specific error messages** (screenshots helpful)
- **Browser and version** (Chrome 95, Firefox 90, etc.)
- **Device type** (Windows PC, Mac, iPhone, etc.)  
- **Steps to reproduce** the problem
- **When problem started** (after what change)
- **Multiple users affected** or just you

### Contact Methods

- **Email**: support@[yourdomain] (fastest response)
- **User Forums**: Community help and tips
- **Live Chat**: Available during business hours
- **Phone**: For urgent issues affecting learning

### Response Time Expectations

- **Critical Issues** (can't access app): 2-4 hours
- **Important Features** (planning, reviews): 24 hours  
- **General Questions**: 48-72 hours
- **Feature Requests**: Next release cycle

## Preventive Measures

### Regular Maintenance

- **Clear browser cache** weekly
- **Update browser** when prompted
- **Backup important data** monthly
- **Test critical features** after updates

### Best Practices

- **Single browser use** for consistency
- **Stable internet** during important planning
- **Regular data exports** for backup
- **Gradual feature adoption** to avoid overwhelm

### System Monitoring

- **Check app status page** before reporting issues
- **Subscribe to announcements** for planned maintenance
- **Test backup plans** for technology failures
- **Have offline alternatives** for critical learning days

---

## Quick Fix Checklist

When something isn't working:

1. ✅ **Refresh the page** (Ctrl+F5 or Cmd+Shift+R)
2. ✅ **Clear browser cache and cookies**  
3. ✅ **Try different browser or incognito mode**
4. ✅ **Check internet connection stability**
5. ✅ **Verify you're logged in correctly**
6. ✅ **Look for JavaScript errors** in browser console
7. ✅ **Wait 5-10 minutes** and try again
8. ✅ **Test on different device** if available

If none of these steps resolve the issue, then contact support with specific details about the problem.

---

**Remember**: Most issues are resolved quickly with basic troubleshooting. Don't let technical problems interrupt your family's learning momentum - try these solutions first, and help is always available when you need it! 🚀