# AI Tech Support Chatbot for PCSC Website

![WORDPRESS](https://img.shields.io/badge/WORDPRESS-21759B?style=for-the-badge&logo=wordpress&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![JAVASCRIPT](https://img.shields.io/badge/JAVASCRIPT-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![CSS](https://img.shields.io/badge/CSS-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![OPENAI CHATKIT](https://img.shields.io/badge/OPENAI%20CHATKIT-412991?style=for-the-badge)
![AI CHATBOT](https://img.shields.io/badge/AI%20CHATBOT-10A37F?style=for-the-badge)

---

AI-powered tech support chatbot integrated into the Poipu Computer Service and Consulting (PCSC) website using WordPress, PHP, JavaScript, CSS, and OpenAI ChatKit.

## Overview

This project integrates an AI-powered tech support chatbot into the Poipu Computer Service and Consulting (PCSC) website. It was built as a custom WordPress plugin using PHP, JavaScript, CSS, and OpenAI ChatKit to provide guided troubleshooting while controlling usage through secure session handling and rate limiting.

## Preview

<table align="center">
  <tr>
    <td align="center">
      <img src="screenshots/chatbot-ui.png" alt="AI Tech Support Chatbot UI" width="280"><br>
      <sub><b>AI Tech Support Chatbot UI</b></sub>
    </td>
    <td width="30"></td>
    <td align="center">
      <img src="screenshots/chatbot-response-example.png" alt="AI Chatbot Response Example" width="280"><br>
      <sub><b>AI Chatbot Response Example</b></sub>
    </td>
  </tr>
</table>

## Key Features

- AI-powered tech support for common IT issues
- Custom WordPress plugin integration
- Secure PHP backend for session token generation
- Branded chat widget built with JavaScript and CSS
- Mobile-responsive interface
- IP-based rate limiting and usage control
- Stable chat initialization and improved reliability

## Tech Stack

### Frontend
![HTML](https://img.shields.io/badge/HTML-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS](https://img.shields.io/badge/CSS-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)

### Backend
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-21759B?style=for-the-badge&logo=wordpress&logoColor=white)

### AI / API
![OpenAI ChatKit](https://img.shields.io/badge/OpenAI%20ChatKit-412991?style=for-the-badge)
![OpenAI API](https://img.shields.io/badge/OpenAI%20API-10A37F?style=for-the-badge)

### Other
![WordPress Transients](https://img.shields.io/badge/WordPress%20Transients-21759B?style=for-the-badge)
![REST API](https://img.shields.io/badge/REST%20API-FF6F00?style=for-the-badge)

## Architecture

```text
User (Browser)
   ↓
Custom JS Widget (Chat UI)
   ↓
Credit Check / Session Validation
   ↓
OpenAI ChatKit Session API
   ↓
AI Response Returned to Frontend
```
## How It Works

1. The user opens the chatbot from the floating launcher on the PCSC website.
2. The frontend widget requests a temporary client session from the WordPress backend.
3. The backend validates usage limits and generates a secure session token.
4. OpenAI ChatKit uses that session to connect the user with the AI agent workflow.
5. The chatbot responds with step-by-step troubleshooting guidance inside the branded chat interface.

## Challenges Solved

- Fixed chat UI initialization issues
- Resolved the issue where the header appeared only after scrolling
- Fixed stuck loading and connecting behavior caused by stale session handling
- Prevented session token reuse by generating fresh secure sessions
- Added IP-based rate limiting and server-side validation to control abuse and API cost
- Maintained custom branding while embedding the AI chat interface

## Project Structure

```text
PCSC-AI-Chatbot/
├── plugin/
│   ├── pcsc-tech-support-advisor.php
│   └── assets/
│       ├── chatkit-widget.css
│       |── chatkit-widget.js
        └── pcsc-logo.png
├── screenshots/
└── .gitignore
└── README.md
```

## Setup
To run this project in a real WordPress environment, you would need:
- a WordPress site
- the custom plugin files in the correct plugin directory
- frontend assets loaded correctly
- OpenAI API credentials configured securely outside the repository
- ChatKit workflow configuration defined in server-side settings

## Future Improvements

- Add user login support
- Build an admin dashboard to monitor usage
- Integrate CRM or ticketing workflow
- Add analytics for common issues and user behavior
- Support live human handoff when needed

## Notes

This repository is a portfolio-safe version of a real-world business project. Sensitive production data, credentials, private configuration details, and any confidential business information have been removed or replaced with placeholders before publication.
