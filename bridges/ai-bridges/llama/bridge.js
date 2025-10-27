// Llama AI Bridge Implementation

const { HfInference } = require('@huggingface/inference');

class LlamaBridge {
  constructor(apiKey) {
    this.client = new HfInference(apiKey);
  }

  async chat(text, model = 'meta-llama/Llama-2-7b-chat-hf') {
    try {
      const response = await this.client.textGeneration({
        model,
        inputs: text,
        parameters: { max_new_tokens: 200 },
      });
      return response.generated_text;
    } catch (error) {
      console.error('Error in Llama chat:', error);
      return 'Sorry, I encountered an error.';
    }
  }
}

module.exports = LlamaBridge;
