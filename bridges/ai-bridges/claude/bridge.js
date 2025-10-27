// Claude AI Bridge Implementation

const Anthropic = require('@anthropic-ai/sdk');

class ClaudeBridge {
  constructor(apiKey) {
    this.client = new Anthropic({ apiKey });
  }

  async chat(messages, model = 'claude-3-sonnet-20240229') {
    try {
      const response = await this.client.messages.create({
        model,
        max_tokens: 1000,
        messages,
      });
      return response.content[0].text;
    } catch (error) {
      console.error('Error in Claude chat:', error);
      return 'Sorry, I encountered an error.';
    }
  }
}

module.exports = ClaudeBridge;
