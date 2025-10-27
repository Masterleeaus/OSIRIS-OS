# Llama AI Integration

Bridge to Llama models for open-source AI.

## Setup

1. Use Hugging Face or Ollama API.
2. Install huggingface-hub: `npm install @huggingface/inference`
3. Set API key: `HUGGINGFACE_API_KEY=your_key`

## Features

- Open-source models
- Customizable
- Local inference options

## Example

```javascript
import { HfInference } from '@huggingface/inference';

const hf = new HfInference(process.env.HUGGINGFACE_API_KEY);

async function chat(text) {
  const response = await hf.textGeneration({
    model: 'meta-llama/Llama-2-7b-chat-hf',
    inputs: text,
    parameters: { max_new_tokens: 200 },
  });
  return response.generated_text;
}
```

## Documentation

- [Hugging Face Inference](https://huggingface.co/inference-api)
