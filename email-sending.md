# Email Sending in FreeCRM

This document provides a comprehensive overview of all email sending mechanisms implemented in the FreeCRM system.

## Overview

FreeCRM implements a sophisticated email system with multiple sending methods, queue management, template processing, and workflow integration. The system supports various email providers, automated workflows, and both manual and scheduled email sending.

## Core Components

### 1. Mailer Class (`vendor/yetiforce/Mailer.php`)

The central email sending component that wraps PHPMailer functionality:

- **Purpose**: Handles all email sending operations
- **Features**:
  - Multiple SMTP provider support
  - Queue management
  - Template processing
  - Attachment handling
  - Individual vs bulk delivery options
  - Error logging and debugging

#### Key Methods:
- `sendFromTemplate()`: Send emails using predefined templates
- `sendByRowQueue()`: Process emails from the queue
- `addMail()`: Add emails to the sending queue
- `test()`: Test SMTP configuration

### 2. Mail Class (`vendor/yetiforce/Mail.php`)

Manages email templates and SMTP configurations:

- **Purpose**: Template and SMTP server management
- **Features**:
  - SMTP server configuration retrieval
  - Email template management
  - Attachment handling from documents
  - Caching for performance

#### Key Methods:
- `getAll()`: Get all SMTP servers
- `getSmtpById()`: Get specific SMTP configuration
- `getDefaultSmtp()`: Get default SMTP server
- `getTempleteList()`: Get available email templates
- `getTempleteDetail()`: Get template details

### 3. EmailParser Class (`vendor/yetiforce/EmailParser.php`)

Processes email content and variables:

- **Purpose**: Parse email templates with dynamic content
- **Features**:
  - Variable substitution
  - Email opt-out handling
  - Content validation
  - Multi-language support

## Email Sending Methods

### 1. Direct Email Sending

**Location**: `vendor/yetiforce/Mailer.php`

```php
$mailer = new \App\Mailer();
$mailer->loadSmtpByID($smtpId)
       ->subject($subject)
       ->content($content)
       ->to($email, $name)
       ->send();
```

**Use Cases**:
- Immediate email sending
- Programmatic email sending
- Custom email implementations

### 2. Template-Based Sending

**Location**: `vendor/yetiforce/Mailer.php::sendFromTemplate()`

```php
\App\Mailer::sendFromTemplate([
    'template' => $templateId,
    'to' => $recipients,
    'recordModel' => $recordModel,
    'smtp_id' => $smtpId
]);
```

**Use Cases**:
- Automated workflow emails
- Notification emails
- Marketing campaigns

### 3. Queue-Based Sending

**Location**: `vendor/yetiforce/Mailer.php::addMail()`

```php
\App\Mailer::addMail([
    'to' => $recipients,
    'subject' => $subject,
    'content' => $content,
    'smtp_id' => $smtpId,
    'priority' => $priority
]);
```

**Use Cases**:
- Bulk email sending
- Scheduled emails
- High-volume operations

### 4. Workflow Email Tasks

#### VTEmailTemplateTask
**Location**: `modules/com_vtiger_workflow/tasks/VTEmailTemplateTask.php`

- Sends emails using predefined templates
- Supports email opt-out checking
- Integrates with workflow conditions

#### VTSendPdf
**Location**: `modules/com_vtiger_workflow/tasks/VTSendPdf.php`

- Sends emails with PDF attachments
- Generates PDFs from templates
- Combines email and document functionality

#### VTSendNotificationTask
**Location**: `modules/com_vtiger_workflow/tasks/VTSendNotificationTask.php`

- Sends calendar invitations
- Handles iCal attachments
- Manages event notifications

## SMTP Configuration

### Supported Mailer Types

The system supports multiple mailer types:

1. **SMTP** (`smtp`): Standard SMTP protocol
2. **Sendmail** (`sendmail`): Unix sendmail command
3. **Mail** (`mail`): PHP mail() function
4. **Qmail** (`qmail`): Qmail mailer

### SMTP Configuration Fields

- `host`: SMTP server hostname
- `port`: SMTP server port
- `secure`: Security protocol (tls, ssl, etc.)
- `authentication`: Authentication required (boolean)
- `username`: SMTP username
- `password`: SMTP password
- `from_email`: Default sender email
- `from_name`: Default sender name
- `reply_to`: Reply-to address
- `individual_delivery`: Send individual emails vs bulk
- `options`: Additional SMTP options (JSON)

### Configuration Management

**Location**: `modules/Settings/MailSmtp/`

- Create/edit SMTP configurations
- Test SMTP connections
- Set default SMTP server
- Manage multiple SMTP accounts

## Email Queue System

### Queue Statuses

- `0`: Pending Acceptance (requires approval)
- `1`: Waiting to be Sent (ready for processing)
- `2`: Error During Sending (failed)

### Queue Processing

**Location**: `cron/Mailer.php`

The cron job processes the email queue:

```php
$dataReader = (new \App\Db\Query())->from('s_#__mail_queue')
    ->where(['status' => 1])
    ->orderBy(['priority' => SORT_DESC, 'date' => SORT_ASC])
    ->limit(AppConfig::performance('CRON_MAX_NUMBERS_SENDING_MAILS'))
    ->createCommand($db)->query();
```

### Queue Features

- Priority-based processing
- Batch processing limits
- Error handling and retry logic
- Automatic cleanup of sent emails
- Manual queue management

## Email Templates

### Template System

**Location**: `vendor/yetiforce/Mail.php`

- Template storage in database
- Dynamic content processing
- Multi-language support
- Attachment support

### Template Variables

The system supports various template variables:

- `$(record : field_name)$`: Record field values
- `$(organization : name)$`: Organization information
- `$(employee : field_name)$`: Employee information
- `$(general : CurrentDate)$`: System information
- `$(translate : key)$`: Translation keys

### Template Processing

**Location**: `vendor/yetiforce/TextParser.php`

- Variable substitution
- Content parsing
- Multi-language support
- Custom function support

## Email Integration Modules

### 1. OSSMail Module

**Location**: `modules/OSSMail/`

- Email client integration
- IMAP/POP3 support
- Email composition
- Address book integration

### 2. OSSMailView Module

**Location**: `modules/OSSMailView/`

- Email viewing and management
- Email categorization
- Related record binding

### 3. OSSMailScanner Module

**Location**: `modules/OSSMailScanner/`

- Automated email processing
- Email-to-record binding
- Scanner actions and rules

## Email Sending Triggers

### 1. Workflow Triggers

- Record creation/modification
- Scheduled workflows
- Manual triggers
- Related record changes

### 2. System Triggers

- User notifications
- System alerts
- Error notifications
- Status changes

### 3. Manual Triggers

- User-initiated sending
- Bulk operations
- Mass email campaigns

## Configuration Options

### Mail Module Configuration

**Location**: `config/modules/Mail.php`

```php
$CONFIG = [
    'MAILTO_LIMIT' => 2030,  // URL character limit
    'RC_COMPOSE_ADDRESS_MODULES' => ['Accounts', 'Contacts', ...],
    'HELPDESK_NEXT_WAIT_FOR_RESPONSE_STATUS' => 'Answered',
    'HELPDESK_OPENTICKET_STATUS' => 'Open',
    'MAILER_REQUIRED_ACCEPTATION_BEFORE_SENDING' => false,
];
```

### Performance Configuration

- `CRON_MAX_NUMBERS_SENDING_MAILS`: Maximum emails per cron run
- `MAILER_DEBUG`: Enable debug logging
- Queue processing limits

## Error Handling and Logging

### Error Types

- SMTP connection errors
- Authentication failures
- Template processing errors
- Queue processing errors

### Logging

- Debug logging for SMTP operations
- Error logging for failed sends
- Trace logging for successful operations
- Queue status tracking

## Security Features

### Email Opt-out Handling

**Location**: `vendor/yetiforce/EmailParser.php`

- Respects user opt-out preferences
- Module-specific opt-out fields
- Automatic filtering of opted-out users

### Authentication

- SMTP authentication support
- Secure connection options (TLS/SSL)
- Password encryption
- User permission checks

## Best Practices

### 1. SMTP Configuration

- Use dedicated SMTP servers for production
- Configure proper authentication
- Set appropriate timeouts
- Monitor SMTP server health

### 2. Queue Management

- Monitor queue size and processing
- Set appropriate batch limits
- Handle failed emails appropriately
- Regular queue cleanup

### 3. Template Design

- Use clear, professional templates
- Test templates thoroughly
- Handle missing data gracefully
- Optimize for different email clients

### 4. Performance

- Use caching for templates and SMTP configs
- Batch similar operations
- Monitor email sending rates
- Optimize database queries

## Troubleshooting

### Common Issues

1. **SMTP Connection Failures**
   - Check server credentials
   - Verify network connectivity
   - Check firewall settings

2. **Template Processing Errors**
   - Validate template syntax
   - Check variable availability
   - Test with sample data

3. **Queue Processing Issues**
   - Check cron job configuration
   - Monitor queue status
   - Review error logs

4. **Email Delivery Problems**
   - Check recipient addresses
   - Verify SMTP server reputation
   - Review spam filters

### Debug Tools

- SMTP test functionality
- Template preview
- Queue status monitoring
- Error log analysis

## Conclusion

The FreeCRM email system provides a comprehensive solution for all email sending needs, from simple notifications to complex automated workflows. The modular design allows for easy customization and extension while maintaining reliability and performance.

Key strengths:
- Multiple sending methods
- Robust queue management
- Template system with variables
- Workflow integration
- Comprehensive error handling
- Security features

The system is designed to handle both low-volume and high-volume email operations while maintaining deliverability and user experience standards.
