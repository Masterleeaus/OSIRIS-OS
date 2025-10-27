// Binance Smart Chain Bridge Implementation

const { ethers } = require('ethers');

class BSCBridge {
  constructor(rpcUrl = 'https://bsc-dataseed1.binance.org/') {
    this.provider = new ethers.providers.JsonRpcProvider(rpcUrl);
  }

  async getBalance(address) {
    const balance = await this.provider.getBalance(address);
    return ethers.utils.formatEther(balance);
  }

  async callContract(contractAddress, abi, method, params = []) {
    const contract = new ethers.Contract(contractAddress, abi, this.provider);
    return contract[method](...params);
  }
}

module.exports = BSCBridge;
