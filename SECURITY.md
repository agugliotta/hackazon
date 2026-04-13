# Security Policy

## This application is intentionally vulnerable

Hackazon is a **vulnerable-by-design** web application built for security training. The following are **not bugs** — they are the product:

- SQL Injection (UNION-based, blind)
- Cross-Site Scripting (Reflected, Stored)
- Cross-Site Request Forgery
- Insecure Direct Object Reference (IDOR)
- Remote File Inclusion
- OS Command Injection
- XML External Entity Injection (XXE)
- Arbitrary File Upload

**Do not open issues for any of the above.** See the vulnerability matrix at `/admin/vulnerability` for the full list of intentional attack surfaces.

## Reporting genuine bugs

A "genuine bug" is something that prevents the application from functioning as a lab — e.g., a crash on startup, a broken Docker build, or a route that returns 500 for a reason unrelated to a vulnerability.

To report a genuine implementation bug, open a GitHub issue with:

- Steps to reproduce
- Expected behaviour
- Actual behaviour (include the Laravel error or stack trace if available)

## Deployment warning

**Never deploy this application on a public server or any network where untrusted users have access.** It is designed to be run locally or in isolated lab environments only.
