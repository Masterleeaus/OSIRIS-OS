
🤝 Contributing to Osiris OS

Osiris OS is a collective project. Every improvement—whether a line of code, a new vertical, a translation, or a bug report—helps the system evolve.

🧭 How to Contribute

1. Fork the repo and create your feature branch:
git checkout -b feature/amazing-feature

2. Commit changes clearly:
git commit -m "Add feature: short description"

3. Push to the branch:
git push origin feature/amazing-feature

4. Open a Pull Request — include screenshots, logs, or examples if relevant.


⚙️ Coding Standards

Use PSR-12 for PHP and Prettier/ESLint defaults for JS.

Keep logic modular: one class = one purpose.

Comment meaningfully. Avoid noise or jokes in code.

Test before committing. Run:

php artisan test
npm run lint


🧩 Documentation & Vertical Guidelines

All changes affecting users or APIs must include doc updates under /docs/devguide/.

New verticals must validate against /docs/landing/vertical-schema.json.

Update the Codex Master Index if adding a new subsystem or Pass.


🔐 Security

Never commit secrets, API keys, or .env files.

Report vulnerabilities privately via security@osiris.system.

AEGIS Proofs handle authentication—no password logic should be written manually.


🧠 Review Philosophy

Constructive: reviews improve code, not ego.

Transparent: decisions and reasoning must be logged.

Traceable: every merge updates the Ledger automatically.


🪙 Contributor Recognition

Every merged PR is permanently recorded in the Osiris Ledger, attributing your work immutably.
Gold Tier contributors receive early access to new verticals and Codex releases.


⚖️ License

By contributing, you agree that your submissions fall under the BSD 3-Clause License governing this project.


