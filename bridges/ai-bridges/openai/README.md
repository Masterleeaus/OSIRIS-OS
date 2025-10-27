# OpenAI GPT Integration

Bridge to OpenAI's GPT models for chat and completion.

## Setup

1. Get OpenAI API key from [platform.openai.com](https://platform.openai.com).
2. Install openai: `npm install openai`
3. Set API key in environment: `OPENAI_API_KEY=your_key`

## Features

- Chat completions
- Text generation
- Model selection (GPT-4, GPT-3.5)

## Example

```javascript
import OpenAI from 'openai';

const openai = new OpenAI({
  apiKey: process.env.OPENAI_API_KEY,
});

const response = await openai.chat.completions.create({
  model: 'gpt-4',
  messages: [{ role: 'user', content: 'Hello!' }],
});

console.log(response.choices[0].message.content);
```

## Documentation

- [OpenAI API Docs](https://platform.openai.com/docs)
