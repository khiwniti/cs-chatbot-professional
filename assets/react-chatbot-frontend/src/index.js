import React from 'react';
import ReactDOM from 'react-dom/client';
import SimpleChatbot from './SimpleChatbot';
// Potentially import global styles if SimpleChatbot.css is not handling everything
// import './index.css'; // If you have a global css file

const rootElement = document.getElementById('cs-react-chatbot-root');
if (rootElement) {
  const root = ReactDOM.createRoot(rootElement);
  root.render(
    <React.StrictMode>
      <SimpleChatbot />
    </React.StrictMode>
  );
} else {
  console.error('Chatbot root element #cs-react-chatbot-root not found.');
}
