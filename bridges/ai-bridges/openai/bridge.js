// OpenAI GPT Bridge Implementation
// Example chat interface with GPT models

const OpenAI = require('openai');

class GPTBridge {
  constructor(apiKey) {
    this.client = new OpenAI({ apiKey });
  }

  async chat(messages, model = 'gpt-4') {
    try {
      const response = await this.client.chat.completions.create({
        model,
        messages,
        max_tokens: 1000,
        temperature: 0.7,
      });
      return response.choices[0].message.content;
    } catch (error) {
      console.error('Error in GPT chat:', error);
      return 'Sorry, I encountered an error.';
    }
  }

  async generateImage(prompt, size = '1024x1024') {
    try {
      const response = await this.client.images.generate({
        model: 'dall-e-3',
        prompt,
        size,
        n: 1,
      });
      return response.data[0].url;
    } catch (error) {
      console.error('Error generating image:', error);
      return null;
    }
  }
}

module.exports = GPTBridge;

// Usage example
async function main() {
  const bridge = new GPTBridge(process.env.OPENAI_API_KEY);

  const response = await bridge.chat([
    { role: 'user', content: 'Hello, how are you?' }
  ]);
  console.log('GPT Response:', response);

  const imageUrl = await bridge.generateImage('A futuristic AI platform');
  console.log('Generated Image:', imageUrl);
}
