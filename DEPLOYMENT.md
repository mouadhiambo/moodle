# Moodle Deployment on Render

This guide will help you deploy your Moodle application to Render using the provided configuration files.

## Prerequisites

1. A Render account
2. Your Moodle application code in a Git repository
3. Basic understanding of Moodle and cloud deployment

## Files Overview

The following files have been created for your Render deployment:

- `render.yaml` - Main Render configuration file
- `config.php` - Production Moodle configuration
- `build.sh` - Build script for installation
- `start.sh` - Web service start script
- `start-cron.sh` - Cron worker start script

## Deployment Steps

### 1. Prepare Your Repository

1. Commit all the configuration files to your Git repository
2. Ensure your repository is accessible to Render

### 2. Create Services on Render

#### Option A: Using Render Dashboard (Recommended)

1. Go to your Render dashboard
2. Click "New +" and select "Blueprint"
3. Connect your Git repository
4. Render will automatically detect the `render.yaml` file and create the services

#### Option B: Manual Service Creation

If you prefer to create services manually:

1. **Web Service:**
   - Type: Web Service
   - Environment: PHP
   - Build Command: `chmod +x build.sh && ./build.sh`
   - Start Command: `chmod +x start.sh && ./start.sh`
   - Plan: Starter (or higher)

2. **Worker Service (Cron):**
   - Type: Background Worker
   - Environment: PHP
   - Build Command: `chmod +x build.sh && ./build.sh`
   - Start Command: `chmod +x start-cron.sh && ./start-cron.sh`
   - Plan: Starter (or higher)

3. **Database:**
   - Type: PostgreSQL
   - Plan: Starter (or higher)

### 3. Environment Variables

Set the following environment variables in your Render services:

#### Web Service Environment Variables:
```
MOODLE_WWWROOT=https://your-app-name.onrender.com
MOODLE_ADMIN_PASSWORD=your-secure-password
MOODLE_ADMIN_EMAIL=admin@yourdomain.com
MOODLE_PASSWORD_PEPPER=your-random-pepper-string
```

#### Database Connection (Auto-configured):
```
MOODLE_DB_TYPE=pgsql
MOODLE_DB_HOST=your-db-host
MOODLE_DB_NAME=moodle
MOODLE_DB_USER=moodle
MOODLE_DB_PASSWORD=your-db-password
```

#### Optional Email Configuration:
```
SMTP_HOST=smtp.your-provider.com
SMTP_USER=your-smtp-username
SMTP_PASS=your-smtp-password
SMTP_SECURE=tls
```

### 4. Deploy

1. Deploy your services
2. Wait for the build process to complete
3. Check the logs for any errors
4. Access your Moodle site at the provided URL

## Post-Deployment Configuration

### 1. Initial Setup

1. Visit your Moodle site
2. Complete the installation wizard if needed
3. Log in with the admin credentials you set

### 2. Configure Email

1. Go to Site Administration > Server > Email
2. Configure SMTP settings if you haven't set them via environment variables
3. Test email functionality

### 3. Configure File Storage

The application uses Render's disk storage for file uploads. Files are stored in `/opt/render/project/src/moodledata`.

### 4. Performance Optimization

1. Go to Site Administration > Development > Purge caches
2. Configure caching settings in Site Administration > Server > Performance
3. Enable compression if needed

## Monitoring and Maintenance

### 1. Logs

- Monitor your web service logs for errors
- Check cron worker logs for scheduled task execution
- Database logs are available in the Render dashboard

### 2. Backups

- Regular database backups are handled by Render
- File backups should be implemented using Moodle's backup system
- Consider setting up automated backups

### 3. Updates

To update Moodle:

1. Update your code in the Git repository
2. Redeploy the services
3. The build script will handle the upgrade process

## Troubleshooting

### Common Issues

1. **Database Connection Errors:**
   - Verify database credentials
   - Check if the database service is running
   - Ensure network connectivity

2. **File Permission Errors:**
   - The build script sets proper permissions
   - Check if the disk storage is properly mounted

3. **Cron Jobs Not Running:**
   - Verify the worker service is running
   - Check cron worker logs
   - Ensure database connectivity

4. **Memory Issues:**
   - Increase the service plan if needed
   - Optimize PHP memory settings
   - Check for memory leaks in logs

### Getting Help

1. Check Render's documentation
2. Review Moodle's deployment guides
3. Check the application logs for specific error messages

## Security Considerations

1. **Change Default Passwords:**
   - Update admin password after deployment
   - Use strong, unique passwords

2. **Environment Variables:**
   - Keep sensitive data in environment variables
   - Don't commit passwords to Git

3. **HTTPS:**
   - Render provides HTTPS by default
   - Ensure all connections use HTTPS

4. **Regular Updates:**
   - Keep Moodle updated
   - Monitor security advisories

## Scaling

As your Moodle usage grows:

1. **Upgrade Service Plans:**
   - Increase web service plan for more resources
   - Upgrade database plan for better performance

2. **Add More Workers:**
   - Create additional cron workers if needed
   - Consider load balancing for high traffic

3. **External Storage:**
   - Consider using external file storage (S3, etc.)
   - Implement CDN for static content

## Cost Optimization

1. **Monitor Usage:**
   - Use Render's monitoring tools
   - Optimize resource usage

2. **Scheduled Scaling:**
   - Scale down during low-usage periods
   - Scale up during peak times

3. **Efficient Caching:**
   - Implement proper caching strategies
   - Reduce database queries

This deployment setup provides a solid foundation for running Moodle on Render with proper scaling, monitoring, and maintenance capabilities.
