# GitHub Webhooks Configuration

This file contains example webhook configurations for GitHub.

## Setup

1. Go to GitHub Repository Settings > Webhooks.
2. Add webhook URL: https://your-server.com/webhooks/github
3. Set Content type: application/json
4. Select events: Push, Pull Request, Release.

## Example Payload

For a push event:

```json
{
  "ref": "refs/heads/main",
  "repository": {
    "name": "AIPlatform",
    "full_name": "REChain-Network-Solutions/AIPlatform"
  },
  "pusher": {
    "name": "username"
  }
}
```

## Handling Webhooks

In your server (e.g., Laravel routes/webhooks.php):

```php
Route::post('/webhooks/github', function (Request $request) {
    $event = $request->header('X-GitHub-Event');
    if ($event === 'push') {
        // Trigger build for all platforms
        dispatch(new BuildPlatformsJob);
    }
    return response()->json(['status' => 'ok']);
});
```

## Security

- Use secret tokens to verify webhook authenticity.
- Set in .env: GITHUB_WEBHOOK_SECRET=your_secret
