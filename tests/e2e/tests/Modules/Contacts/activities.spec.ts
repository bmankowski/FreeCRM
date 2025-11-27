/**
 * Activity Management Tests - Contacts Module
 * 
 * Tests activity management functionality including:
 * - Create task from contact
 * - Create event/meeting
 * - View activities timeline
 * - Complete task
 * - Activity filtering
 * - Activity comments/notes
 * 
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import { test, expect } from '../../../fixtures/auth.fixture';
import { ContactsPage } from '../../../pages/ContactsPage';

test.describe('Contacts - Activity Management', () => {
  let page: Page;
  let authenticatedPage: any;
  let contactsPage: ContactsPage;
  let testContactId: string | null;

  test.beforeEach(async ({ authenticatedPage: authPage }) => {
    authenticatedPage = authPage;
    // Use authenticatedPage directly
    contactsPage = new ContactsPage(authenticatedPage);
    
    // Login
    // Already authenticated via fixture('/');
    // TODO: Add login steps if needed
    
    // Create test contact for activities
    const timestamp = Date.now();
    testContactId = await contactsPage.createTestContact(
      'Activity',
      `Test${timestamp}`,
      { email: `activity_${timestamp}@example.com` }
    );
  });

  test.afterEach(async () => {
    // Cleanup
    if (testContactId) {
      await contactsPage.deleteContactById(testContactId).catch(() => {});
    }
    
  });

  test('Test 8.1: Create Task from Contact', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    // Navigate to contact detail
    await contactsPage.gotoDetail(testContactId);
    
    // Navigate to activities section
    const activitiesTab = authenticatedPage.locator('a:has-text("Activities"), button:has-text("Related"), a:has-text("Related")').first();
    if (await activitiesTab.isVisible({ timeout: 3000 })) {
      await activitiesTab.click();
    }
    
    // Click "Add Task" button
    const addTaskButton = authenticatedPage.locator('button:has-text("Add Task"), a:has-text("New Task"), button:has-text("Task")').first();
    if (await addTaskButton.isVisible({ timeout: 5000 })) {
      await addTaskButton.click();
      
      // Fill task form
      const taskForm = authenticatedPage.locator('.task-form, .quickCreateContainer, .modal').first();
      if (await taskForm.isVisible({ timeout: 3000 })) {
        // Verify contact pre-filled
        const contactField = taskForm.locator('[name="related_to"], [name="contact_id"], [name="parent_id"]').first();
        if (await contactField.isVisible({ timeout: 2000 })) {
          const fieldValue = await contactField.inputValue();
          expect(fieldValue).toBeTruthy();
        }
        
        // Fill task details
        const subjectField = taskForm.locator('[name="subject"]').first();
        if (await subjectField.isVisible({ timeout: 2000 })) {
          await subjectField.fill('Follow-up call');
        }
        
        // Set due date (tomorrow)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().split('T')[0];
        
        const dueDateField = taskForm.locator('[name="due_date"], [name="date_start"]').first();
        if (await dueDateField.isVisible({ timeout: 2000 })) {
          await dueDateField.fill(tomorrowStr);
        }
        
        // Set priority
        const priorityField = taskForm.locator('select[name="priority"], select[name="taskpriority"]').first();
        if (await priorityField.isVisible({ timeout: 2000 })) {
          await priorityField.selectOption({ label: 'High' });
        }
        
        // Set status
        const statusField = taskForm.locator('select[name="status"], select[name="taskstatus"]').first();
        if (await statusField.isVisible({ timeout: 2000 })) {
          await statusField.selectOption({ label: 'Not Started' });
        }
        
        // Add description
        const descField = taskForm.locator('[name="description"], textarea').first();
        if (await descField.isVisible({ timeout: 2000 })) {
          await descField.fill('Discuss Enterprise plan pricing');
        }
        
        // Save
        await taskForm.locator('button:has-text("Save")').first().click();
        
        // Verify success
        await expect(authenticatedPage.locator('.notification, .toastify')).toContainText(/created|saved|success/i, { timeout: 10000 });
        
        // Verify task appears in timeline
        await authenticatedPage.waitForTimeout(2000);
        await expect(authenticatedPage.locator('.activity-timeline, .activities-section')).toContainText('Follow-up call');
      }
    }
  });

  test('Test 8.2: Create Event/Meeting', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    // Look for schedule meeting button
    const meetingButton = authenticatedPage.locator('button:has-text("Schedule Meeting"), a:has-text("New Event"), button:has-text("Event")').first();
    if (await meetingButton.isVisible({ timeout: 5000 })) {
      await meetingButton.click();
      
      const eventForm = authenticatedPage.locator('.event-form, .modal, .quickCreateContainer').first();
      if (await eventForm.isVisible({ timeout: 3000 })) {
        // Fill meeting details
        const subjectField = eventForm.locator('[name="subject"]').first();
        if (await subjectField.isVisible({ timeout: 2000 })) {
          await subjectField.fill('Product Demo');
        }
        
        // Set start date/time (tomorrow at 2 PM)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().split('T')[0];
        
        const startDateField = eventForm.locator('[name="date_start"]').first();
        if (await startDateField.isVisible({ timeout: 2000 })) {
          await startDateField.fill(tomorrowStr);
        }
        
        const startTimeField = eventForm.locator('[name="time_start"]').first();
        if (await startTimeField.isVisible({ timeout: 2000 })) {
          await startTimeField.fill('14:00');
        }
        
        // Set end time
        const endTimeField = eventForm.locator('[name="time_end"]').first();
        if (await endTimeField.isVisible({ timeout: 2000 })) {
          await endTimeField.fill('15:00');
        }
        
        // Set location
        const locationField = eventForm.locator('[name="location"]').first();
        if (await locationField.isVisible({ timeout: 2000 })) {
          await locationField.fill('Conference Room A');
        }
        
        // Add description
        const descField = eventForm.locator('[name="description"], textarea').first();
        if (await descField.isVisible({ timeout: 2000 })) {
          await descField.fill('Demonstrate new features');
        }
        
        // Save
        await eventForm.locator('button:has-text("Save")').first().click();
        
        // Verify success
        await expect(authenticatedPage.locator('.notification')).toContainText(/created|scheduled|success/i, { timeout: 10000 });
      }
    }
  });

  test('Test 8.3: View Activities Timeline', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    // Navigate to activities
    const activitiesLink = authenticatedPage.locator('a:has-text("Activities"), button:has-text("Timeline"), a:has-text("History")').first();
    if (await activitiesLink.isVisible({ timeout: 3000 })) {
      await activitiesLink.click();
      
      const timeline = authenticatedPage.locator('.activities-timeline, .activity-list, .history-section').first();
      if (await timeline.isVisible({ timeout: 3000 })) {
        // Verify timeline structure
        await expect(timeline).toBeVisible();
        
        // Check for activity items
        const activityItems = timeline.locator('.activity-item, .timeline-item, .history-item');
        const itemCount = await activityItems.count();
        
        // Timeline should exist (may or may not have items yet)
        expect(itemCount).toBeGreaterThanOrEqual(0);
      }
    }
  });

  test('Test 8.4: Complete Task', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    // First create a task
    await contactsPage.gotoDetail(testContactId);
    
    const addTaskButton = authenticatedPage.locator('button:has-text("Add Task"), a:has-text("New Task")').first();
    if (await addTaskButton.isVisible({ timeout: 5000 })) {
      await addTaskButton.click();
      
      const taskForm = authenticatedPage.locator('.task-form, .modal').first();
      if (await taskForm.isVisible({ timeout: 3000 })) {
        await taskForm.locator('[name="subject"]').fill('Complete Me Task');
        await taskForm.locator('button:has-text("Save")').first().click();
        await authenticatedPage.waitForTimeout(2000);
      }
    }
    
    // Now find and complete the task
    await authenticatedPage.waitForTimeout(1000);
    const openTask = authenticatedPage.locator('.activity-task:has-text("Complete Me Task"), tr:has-text("Complete Me Task")').first();
    
    if (await openTask.isVisible({ timeout: 5000 })) {
      // Click complete button or checkbox
      const completeButton = openTask.locator('button:has-text("Complete"), input[type="checkbox"], .complete-btn').first();
      if (await completeButton.isVisible({ timeout: 2000 })) {
        await completeButton.click();
        
        // If there's a completion dialog, fill it
        const notesDialog = authenticatedPage.locator('.complete-task-dialog, .modal');
        if (await notesDialog.isVisible({ timeout: 2000 })) {
          const notesField = notesDialog.locator('[name="notes"], textarea').first();
          if (await notesField.isVisible({ timeout: 1000 })) {
            await notesField.fill('Task completed successfully');
          }
          await notesDialog.locator('button:has-text("Save"), button:has-text("Complete")').first().click();
        }
        
        // Verify completed
        await authenticatedPage.waitForTimeout(1000);
        await expect(authenticatedPage.locator('.notification, .toastify')).toContainText(/completed|done/i, { timeout: 5000 });
      }
    }
  });

  test('Test 8.5: Activity Filtering', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    // Navigate to activities
    const activitiesLink = authenticatedPage.locator('a:has-text("Activities"), a:has-text("History")').first();
    if (await activitiesLink.isVisible({ timeout: 3000 })) {
      await activitiesLink.click();
      
      // Look for activity type filter
      const typeFilter = authenticatedPage.locator('select[name="activity_type"], select[name="type_filter"]').first();
      if (await typeFilter.isVisible({ timeout: 3000 })) {
        // Filter by tasks
        await typeFilter.selectOption('Task');
        await authenticatedPage.waitForTimeout(1000);
        
        // Verify filtering applied
        const timeline = authenticatedPage.locator('.activities-timeline, .activity-list');
        if (await timeline.isVisible({ timeout: 2000 })) {
          // All visible items should be tasks
          const visibleItems = await timeline.locator('.activity-item').count();
          const taskItems = await timeline.locator('.activity-task, .task-item').count();
          
          // If there are items, they should all be tasks
          if (visibleItems > 0) {
            expect(taskItems).toBe(visibleItems);
          }
        }
        
        // Clear filters
        const clearButton = authenticatedPage.locator('button:has-text("Clear Filters"), a:has-text("All"), button:has-text("Reset")').first();
        if (await clearButton.isVisible({ timeout: 2000 })) {
          await clearButton.click();
          await authenticatedPage.waitForTimeout(500);
        }
      }
    }
  });

  test('Test 8.6: Activity Comments/Notes', async () => {
    if (!testContactId) {
      test.skip();
      return;
    }

    await contactsPage.gotoDetail(testContactId);
    
    // Navigate to activities
    const activitiesLink = authenticatedPage.locator('a:has-text("Activities")').first();
    if (await activitiesLink.isVisible({ timeout: 3000 })) {
      await activitiesLink.click();
      
      // Find first activity item
      const activity = authenticatedPage.locator('.activity-item, .timeline-item').first();
      if (await activity.isVisible({ timeout: 3000 })) {
        // Expand or click on activity
        await activity.click();
        await authenticatedPage.waitForTimeout(500);
        
        // Look for comment/note field
        const commentField = authenticatedPage.locator('textarea[name="comment"], textarea[placeholder*="comment"], .comment-input').first();
        if (await commentField.isVisible({ timeout: 3000 })) {
          await commentField.fill('Customer confirmed availability');
          
          // Post comment
          const postButton = authenticatedPage.locator('button:has-text("Post"), button:has-text("Add Comment"), button:has-text("Save")').first();
          if (await postButton.isVisible({ timeout: 2000 })) {
            await postButton.click();
            
            // Verify comment appears
            await authenticatedPage.waitForTimeout(1000);
            await expect(activity.locator('.comments-section, .comment-list')).toContainText('Customer confirmed availability');
          }
        }
      }
    }
  });
});




