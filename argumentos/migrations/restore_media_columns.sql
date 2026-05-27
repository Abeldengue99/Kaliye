-- RESTORE MEDIA COLUMNS IN PROJECTS TABLE
-- Use this if you are getting "unknown column video_url" or "image_url" errors
-- run in your MySQL terminal or phpMyAdmin

ALTER TABLE `projects` 
ADD COLUMN `image_url` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `video_url` VARCHAR(255) DEFAULT NULL;

-- If you also need to make sure 'is_verified' and 'is_public' are INT (0 or 1) as per recent updates
-- but want to be safe, verify the table status first.
-- This script only adds the missing media columns that the frontend expects.
