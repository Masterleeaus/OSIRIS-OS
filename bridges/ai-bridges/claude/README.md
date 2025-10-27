# Claude AI Integration

Bridge to Anthropic's Claude model for advanced reasoning.

## Setup

1. Get API key from [Anthropic](https://console.anthropic.com/).
2. Install anthropic-sdk: `npm install anthropic`
3. Set API key: `ANTHROPIC_API_KEY=your_key`

## Features

- Advanced reasoning
- Context understanding
- Ethical AI responses

## Example

```javascript
import Anthropic from '@anthropic-ai/sdk';

const anthropic = new Anthropic({
  apiKey: process.env.ANTHROPIC_API_KEY,
});

async function chat(messages) {
  const response = await anthropic.messages.create({
    model: 'claude-3-sonnet-20240229',
    max_tokens: 1000,
    messages,
  });
  return response.content[0].text;
}
```

## Documentation

- [Claude API Docs](https://docs.anthropic.com/claude/reference)
