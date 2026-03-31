import React from 'react';

export default function HelloWidget({ title = 'React Island', subtitle = 'Running inside Vue' }) {
  return (
    <div
      style={{
        padding: '10px 12px',
        borderRadius: 14,
        border: '1px solid rgba(16, 24, 40, 0.10)',
        background: 'rgba(255,255,255,0.9)',
      }}
    >
      <div style={{ fontSize: 12, fontWeight: 800, letterSpacing: 0.6 }}>{title}</div>
      <div style={{ fontSize: 12, opacity: 0.7 }}>{subtitle}</div>
    </div>
  );
}
