// Backlog Management System Implementation

class BacklogManager {
  constructor() {
    this.backlogs = [];
  }

  createBacklog(title, description, priority = 'medium') {
    const backlog = {
      id: Date.now(),
      title,
      description,
      priority,
      tasks: [],
      createdAt: new Date()
    };

    this.backlogs.push(backlog);
    return backlog;
  }

  addTask(backlogId, task) {
    const backlog = this.backlogs.find(b => b.id === backlogId);
    if (backlog) {
      backlog.tasks.push({
        id: Date.now(),
        ...task,
        status: 'pending'
      });
    }
  }

  updateTaskStatus(backlogId, taskId, status) {
    const backlog = this.backlogs.find(b => b.id === backlogId);
    if (backlog) {
      const task = backlog.tasks.find(t => t.id === taskId);
      if (task) {
        task.status = status;
        task.updatedAt = new Date();
      }
    }
  }

  getBacklogs() {
    return this.backlogs;
  }

  integrateWithGit(backlogId, platform = 'github') {
    // Create GitHub/GitLab issues from backlog
    console.log(`Creating ${platform} issue for backlog ${backlogId}`);
  }
}

module.exports = BacklogManager;
