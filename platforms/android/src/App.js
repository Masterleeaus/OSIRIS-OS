import React, { useState } from 'react';
import { View, Text, StyleSheet, Button } from 'react-native';

const App = () => {
  const [response, setResponse] = useState('Press the button to get AI response');

  const getAIResponse = () => {
    setResponse('AI Response: Hello from AIPlatform Android app!');
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>AIPlatform Android</Text>
      <Text style={styles.subtitle}>Decentralized AI on Mobile</Text>
      <Button title="Get AI Response" onPress={getAIResponse} />
      <Text style={styles.response}>{response}</Text>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#f5f5f5',
    padding: 20,
  },
  title: {
    fontSize: 24,
    fontWeight: 'bold',
    color: '#333',
  },
  subtitle: {
    fontSize: 16,
    color: '#666',
    marginTop: 10,
    marginBottom: 20,
  },
  response: {
    fontSize: 16,
    color: '#333',
    marginTop: 20,
    textAlign: 'center',
  },
});

export default App; 
