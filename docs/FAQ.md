# FAQ

**Q: Is AIPlatform production-ready?**  
A: Core features are ready; treat quantum modules as experimental. Follow `DEPLOYMENT.md` for production checklist.

**Q: Can I bring my own model provider?**  
A: Yes — implement the provider interface in `gen-ai-engine/providers/` and add configuration.

**Q: Does it store user data?**  
A: Yes — object storage for artifacts, DB for metadata. See `SECURITY.md` and `PRIVACY.md` (if added) for policies.

**Q: How do I contribute models?**  
A: Open a PR following `CONTRIBUTING.md`, include tests and example pipelines.
