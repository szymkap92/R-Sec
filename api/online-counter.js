// Vercel Serverless Function dla licznika online
// File: /api/online-counter.js

const fs = require('fs');
const path = require('path');

// Przechowywanie w /tmp (jedyne miejsce z write access na Vercel)
const SESSIONS_FILE = '/tmp/online_sessions.json';
const TIMEOUT = 30; // 30 sekund

function loadSessions() {
    try {
        if (fs.existsSync(SESSIONS_FILE)) {
            const data = fs.readFileSync(SESSIONS_FILE, 'utf8');
            return JSON.parse(data);
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
    }
    return {};
}

function saveSessions(sessions) {
    try {
        fs.writeFileSync(SESSIONS_FILE, JSON.stringify(sessions, null, 2));
    } catch (error) {
        console.error('Error saving sessions:', error);
    }
}

function cleanExpiredSessions(sessions) {
    const currentTime = Math.floor(Date.now() / 1000);
    const cleaned = {};
    
    for (const [sessionId, session] of Object.entries(sessions)) {
        if ((currentTime - session.lastSeen) < TIMEOUT) {
            cleaned[sessionId] = session;
        }
    }
    
    return cleaned;
}

export default function handler(req, res) {
    // CORS headers
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
    
    if (req.method === 'OPTIONS') {
        return res.status(200).end();
    }
    
    try {
        let sessions = loadSessions();
        sessions = cleanExpiredSessions(sessions);
        
        if (req.method === 'POST') {
            const { sessionId } = req.body || {};
            
            if (sessionId) {
                sessions[sessionId] = {
                    lastSeen: Math.floor(Date.now() / 1000),
                    userAgent: req.headers['user-agent'] || 'Unknown',
                    ip: req.headers['x-forwarded-for'] || req.connection.remoteAddress || 'Unknown'
                };
                saveSessions(sessions);
            }
        }
        
        const onlineCount = Object.keys(sessions).length;
        
        res.status(200).json({
            success: true,
            onlineCount: onlineCount,
            timestamp: Math.floor(Date.now() / 1000)
        });
        
    } catch (error) {
        console.error('API Error:', error);
        res.status(500).json({
            success: false,
            error: 'Server error',
            onlineCount: 0
        });
    }
}