// Solana Bridge Implementation

const { Connection, PublicKey, LAMPORTS_PER_SOL, SystemProgram, sendAndConfirmTransaction, Transaction, Keypair } = require('@solana/web3.js');

class SolanaBridge {
  constructor(endpoint = 'https://api.mainnet-beta.solana.com') {
    this.connection = new Connection(endpoint, 'confirmed');
  }

  async getBalance(publicKeyString) {
    const publicKey = new PublicKey(publicKeyString);
    const balance = await this.connection.getBalance(publicKey);
    return balance / LAMPORTS_PER_SOL;
  }

  async sendTransaction(fromKeypair, toPublicKeyString, amount) {
    const toPublicKey = new PublicKey(toPublicKeyString);
    const transaction = new Transaction().add(
      SystemProgram.transfer({
        fromPubkey: fromKeypair.publicKey,
        toPubkey: toPublicKey,
        lamports: amount * LAMPORTS_PER_SOL,
      })
    );
    return await sendAndConfirmTransaction(this.connection, transaction, [fromKeypair]);
  }
}

module.exports = SolanaBridge;
