# Homeschool Learning App - Complete Setup Guide

Welcome to the Homeschool Learning App! This comprehensive guide will help you set up and start using the app to manage your family's homeschool learning journey.

## Table of Contents

1. [Quick Start](#quick-start)
2. [System Requirements](#system-requirements)
3. [Installation](#installation)
4. [Initial Configuration](#initial-configuration)
5. [First-Time User Setup](#first-time-user-setup)
6. [Core Features Overview](#core-features-overview)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)
9. [Advanced Features](#advanced-features)
10. [Support](#support)

## Quick Start

If you're eager to get started, follow these essential steps:

1. **Register** your account at `/register`
2. **Add your first child** with their age and independence level
3. **Create subjects** like Math, English, Science
4. **Set up the weekly calendar** with learning time blocks
5. **Start planning** by creating sessions and scheduling them
6. **Use the daily dashboard** to track progress

## System Requirements

### Minimum Requirements

- **Web Browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Screen Resolution**: 1024x768 minimum (responsive design supports mobile)
- **Internet Connection**: Stable broadband for real-time updates
- **JavaScript**: Must be enabled

### Recommended Setup

- **Screen Resolution**: 1920x1080 or higher for optimal experience
- **Multiple Monitors**: Helpful for planning while teaching
- **Tablet/Mobile**: iOS 14+ or Android 8+ for on-the-go access

## Installation

This is a web-based application - no installation required! Simply visit the application URL in your web browser.

### For Administrators (Self-Hosting)

If you're setting up your own instance:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/your-org/homeschool-learning-app.git
   cd homeschool-learning-app
   ```

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Set up environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure Supabase** (see [Supabase Setup](#supabase-setup))

5. **Run the application**:
   ```bash
   npm run dev      # Start frontend build process
   php artisan serve # Start Laravel server
   ```

## Initial Configuration

### Supabase Setup

1. **Create a Supabase Project**:
   - Go to [supabase.com](https://supabase.com)
   - Create a new project
   - Note your project URL and API keys

2. **Configure Environment Variables**:
   ```env
   SUPABASE_URL=https://your-project.supabase.co
   SUPABASE_ANON_KEY=your-anon-key
   SUPABASE_SERVICE_KEY=your-service-key
   ```

3. **Run Database Migrations**:
   ```bash
   php run-homeschool-migrations.php
   ```
   Or copy the SQL from `database/homeschool-schema.sql` into your Supabase SQL editor.

4. **Configure Authentication**:
   - Go to Supabase Dashboard → Authentication
   - Set redirect URLs: `http://localhost:8000/auth/confirm`
   - Optional: Disable email confirmation for development

## First-Time User Setup

### Step 1: Create Your Account

1. Visit the registration page
2. Enter your name, email, and secure password
3. Verify your email (if confirmation is enabled)
4. Login to access the dashboard

### Step 2: Add Your Children

1. Navigate to **Children** section
2. Click **"Add Child"**
3. Enter:
   - **Name**: Your child's name
   - **Age**: Current age (affects recommendations)
   - **Independence Level**:
     - **Level 1**: Parent does everything
     - **Level 2**: Child can reorder daily tasks
     - **Level 3**: Child can move tasks within the week
     - **Level 4**: Full independence with planning

### Step 3: Create Subjects

1. Go to **Subjects** section
2. Click **"Add Subject"**
3. Choose subjects like:
   - Mathematics
   - English Language Arts
   - Science
   - History
   - Art
   - Music
4. Assign colors for visual organization

### Step 4: Set Up Your Weekly Calendar

1. Visit **Calendar** section
2. For each child, add **Time Blocks**:
   - **Morning Block**: 9:00 AM - 11:30 AM
   - **Afternoon Block**: 1:00 PM - 3:00 PM
   - **Flexible Block**: 4:00 PM - 5:00 PM
3. Set commitment types:
   - **Fixed**: Cannot be moved (external classes)
   - **Preferred**: Ideal time but flexible
   - **Flexible**: Can move as needed

### Step 5: Create Learning Units

1. Click on a subject
2. Add **Units** (curriculum sections):
   - Example: "Multiplication Tables" for Math
   - Set target completion dates
   - Add descriptions

### Step 6: Add Topics

1. Click on a unit
2. Add specific **Topics**:
   - Example: "Times Tables 1-3", "Times Tables 4-6"
   - Set estimated duration (15-45 minutes based on age)
   - Add learning objectives

## Core Features Overview

### Dashboard System

#### Parent Dashboard
- **Multi-child overview** with weekly progress
- **Quick actions** for common tasks
- **Capacity monitoring** to prevent overload
- **Today's priorities** across all children

#### Child Dashboard
- **Today's sessions** (max 3 for focus)
- **Simple interface** appropriate for independence level
- **Progress celebration** and motivation
- **Review reminders**

### Planning Board

The Planning Board uses a Kanban-style workflow:

1. **Backlog**: All available topics not yet planned
2. **Planned**: Topics selected for upcoming learning
3. **Scheduled**: Sessions assigned to specific days/times  
4. **Done**: Completed sessions

#### Quality Validation

The system provides age-appropriate guidance:

- **Session Length**: Automatically suggests appropriate durations
- **Daily Limits**: Warns about overloading
- **Subject Balance**: Ensures variety in learning
- **Break Reminders**: Suggests rest periods

### Flexible Scheduling

#### Commitment Types
- **Fixed**: External classes, appointments (cannot move)
- **Preferred**: Ideal times but can be rescheduled
- **Flexible**: Move freely as needed

#### Catch-up System
- **Automatic Creation**: When sessions are skipped
- **Priority Levels**: 1 (critical) to 5 (later)
- **Smart Redistribution**: AI-powered rescheduling
- **Parent Oversight**: Review and approve changes

### Review System (Spaced Repetition)

#### Setup Review Slots
1. Go to **Reviews** section
2. Choose child and click **"Manage Review Slots"**
3. Add daily review times (15-30 minutes recommended)
4. Set maximum reviews per session

#### How Reviews Work
- **Automatic Scheduling**: Based on performance and time intervals
- **Four Difficulty Levels**: Again, Hard, Good, Easy
- **Adaptive Intervals**: Successful reviews increase time between repetitions
- **Progress Tracking**: Monitor retention rates and learning efficiency

### Calendar Integration

#### Import External Calendars
1. Go to **Calendar** → **Import**
2. Choose method:
   - **File Upload**: Upload .ics file
   - **URL Feed**: Subscribe to recurring calendar (e.g., co-op classes)
3. Preview events before importing
4. Imported events are marked as "Fixed" commitment type

#### Supported Formats
- Standard ICS/iCal files
- Google Calendar exports
- Outlook calendar exports
- Any RFC 5545 compliant calendar

## Best Practices

### Age-Appropriate Scheduling

#### Early Elementary (Ages 5-8)
- **Session Length**: 15-25 minutes maximum
- **Daily Learning**: 1.5-2.5 hours total
- **Frequent Breaks**: Every 15-20 minutes
- **Morning Focus**: Schedule challenging subjects early

#### Elementary (Ages 9-12)
- **Session Length**: 25-45 minutes
- **Daily Learning**: 3-4 hours total  
- **Breaks**: Every 30-45 minutes
- **Subject Variety**: Rotate between different types of thinking

#### Middle School (Ages 13-15)
- **Session Length**: 45-60 minutes
- **Daily Learning**: 4-6 hours total
- **Project Time**: Longer blocks for deeper work
- **Independence**: Gradually increase self-management

#### High School (Ages 16+)
- **Session Length**: 60-90 minutes
- **Daily Learning**: 5-8 hours total
- **Flexibility**: Student-driven scheduling
- **Real-world Integration**: Connect learning to interests/goals

### Planning Strategies

#### Weekly Planning Rhythm
1. **Sunday**: Review previous week and plan upcoming week
2. **Daily Check-ins**: 5-minute morning planning session
3. **Mid-week Adjustment**: Wednesday review and adjust if needed
4. **Friday Reflection**: Celebrate wins and note improvements

#### Managing Multiple Children
- **Staggered Schedules**: Start children at different times
- **Shared Subjects**: Combine ages for history, science experiments
- **Independent Work**: Use review sessions while teaching others
- **Group Activities**: Reading time, educational games

#### Preventing Overwhelm
- **Buffer Time**: Leave 20% of schedule unplanned
- **Quality Over Quantity**: Better to do fewer topics well
- **Regular Reviews**: Use quality analysis to adjust workload
- **Child Input**: Ask children about their capacity and preferences

## Troubleshooting

### Common Issues

#### Sessions Not Showing in Calendar
**Problem**: Created sessions don't appear in calendar view
**Solution**: 
- Ensure sessions are moved to "Scheduled" status in Planning Board
- Check that correct child is selected in both Planning and Calendar
- Verify day of week is set correctly

#### Reviews Not Generating
**Problem**: No review items appear despite completed sessions
**Solution**:
- Set up Review Slots in Reviews → Manage Review Slots
- Ensure sessions are marked as completed (not just done)
- Check that review intervals have elapsed (starts at 1-3 days)

#### Quality Analysis Shows Warnings
**Problem**: Age-appropriate warnings about schedule
**Solution**:
- Review recommendations in Quality Analysis
- Adjust session lengths for child's age
- Spread subjects across more days
- Add breaks between intensive sessions

#### Cache Issues / Slow Loading
**Problem**: Data takes long to load or appears outdated
**Solution**:
- Refresh the browser page
- Clear browser cache and cookies
- Check internet connection
- Contact support if issues persist

#### Import Calendar Fails
**Problem**: ICS file won't import or shows errors
**Solution**:
- Verify file is valid ICS format
- Check file size is under 5MB
- Try exporting calendar again from source
- Use URL import for live calendars

### Performance Tips

#### Optimizing Load Times
- **Use latest browser version** for best performance
- **Close unused tabs** to free memory
- **Stable internet** required for real-time updates
- **Clear cache periodically** if experiencing slowness

#### Managing Large Families
- **Stagger login times** to avoid peak usage
- **Use child-specific views** instead of parent overview for daily use
- **Archive completed units** to reduce data load
- **Regular cleanup** of old sessions and reviews

## Advanced Features

### API Integration

For advanced users, the app provides API endpoints for:
- **Automated Data Import**: Bulk import from other systems
- **Custom Reporting**: Export data to spreadsheets
- **Third-party Integration**: Connect with other educational tools

### Custom Scheduling Rules

Configure advanced scheduling preferences:
- **Subject Sequencing**: Math before language arts
- **Energy Management**: Hardest subjects when child is fresh
- **Sibling Coordination**: Ensure quiet time aligns
- **External Constraints**: Work around family commitments

### Bulk Operations

Efficient management for large curricula:
- **Bulk Session Creation**: Create multiple sessions at once
- **Mass Scheduling**: Apply templates to multiple children
- **Batch Updates**: Change multiple items simultaneously
- **Data Export**: Backup your complete curriculum

### Advanced Analytics

Track learning patterns over time:
- **Progress Trends**: Monthly and yearly progress charts
- **Time Analysis**: How long topics actually take vs. estimates
- **Retention Patterns**: Which subjects need more review
- **Capacity Optimization**: Find ideal daily/weekly loads

## Support

### Getting Help

#### Documentation
- **This Setup Guide**: Comprehensive initial setup
- **User Manual**: Detailed feature explanations
- **FAQ Section**: Common questions and answers
- **Video Tutorials**: Visual walkthroughs

#### Community Support
- **User Forums**: Connect with other homeschool families
- **Feature Requests**: Suggest improvements
- **Success Stories**: Share what's working
- **Tips and Tricks**: Learn from experienced users

#### Technical Support
- **Bug Reports**: Report issues for quick resolution
- **Performance Problems**: Get help optimizing your setup
- **Integration Questions**: Assistance with calendar imports and exports
- **Account Issues**: Help with login and access problems

### Best Practice Resources

#### Educational Philosophy
- **Charlotte Mason Method**: Living books and narration support
- **Classical Education**: Trivium stage adaptations
- **Unit Studies**: Cross-curricular planning
- **Montessori Approach**: Child-led learning integration

#### Curriculum Mapping
- **State Standards**: Align learning with requirements
- **Scope and Sequence**: Plan multi-year progressions
- **Assessment Integration**: Track mastery and gaps
- **Portfolio Development**: Document learning journey

---

## Quick Reference Card

### Essential Daily Workflow
1. **Morning**: Check child's daily dashboard
2. **During**: Mark sessions complete as they happen
3. **Evening**: Review progress and plan tomorrow
4. **Weekly**: Use planning board to schedule upcoming sessions

### Emergency Adjustments
- **Sick Day**: Use "Skip Day" to move sessions to catch-up
- **Schedule Change**: Drag sessions to new days (independence level 3+)
- **Overwhelming**: Check Quality Analysis for recommendations
- **Behind Schedule**: Use catch-up redistribution feature

### Key Shortcuts
- **Quick Complete**: Bulk mark all today's sessions done
- **Fast Planning**: Drag topics directly from backlog to scheduled
- **Rapid Review**: Use review slots for efficient spaced repetition
- **Parent Override**: Take control regardless of independence level

---

**Welcome to organized, joyful homeschooling!** 🎓

This app is designed to reduce your planning overhead while maximizing learning effectiveness. Start simple, use what works, and gradually explore advanced features as you get comfortable.

Remember: The best homeschool system is the one your family actually uses consistently. Focus on building sustainable habits rather than perfect schedules.