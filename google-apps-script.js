// ════════════════════════════════════════════════════════════════
//  Master Analytic — Lead Capture Script
//
//  DEPLOY STEPS:
//  ─────────────────────────────────────────────────────────────
//  1. Open sheet: https://docs.google.com/spreadsheets/d/1l6GjVtcNll1H9osKDKbMmxB35z5vRbvTar5SaMzCLfg
//  2. Extensions → Apps Script
//  3. Delete ALL existing code → paste this file → Save (Ctrl+S)
//  4. Click "Deploy" → "New deployment"
//  5. Click ⚙️ gear icon → "Web app"
//  6. Execute as: Me  |  Who has access: Anyone
//  7. Click Deploy → Authorize → copy Web App URL → update send_mail.php
// ════════════════════════════════════════════════════════════════

var SHEET_ID   = '1l6GjVtcNll1H9osKDKbMmxB35z5vRbvTar5SaMzCLfg';
var SHEET_NAME = 'Leads';
var NOTIFY_TO  = 'masteranalytics.india@gmail.com';

// ── Sheet ──────────────────────────────────────────────────────
function getSheet() {
  var ss = SpreadsheetApp.openById(SHEET_ID);
  var sh = ss.getSheetByName(SHEET_NAME);
  if (!sh) {
    sh = ss.insertSheet(SHEET_NAME);
    sh.appendRow(['Timestamp', 'Name', 'Phone', 'Email', 'City', 'Source']);
    var hdr = sh.getRange(1, 1, 1, 6);
    hdr.setFontWeight('bold').setBackground('#06142E').setFontColor('#fff');
    sh.setFrozenRows(1);
  }
  return sh;
}

function saveRow(d) {
  var ts = Utilities.formatDate(new Date(), 'Asia/Kolkata', 'dd-MM-yyyy HH:mm:ss');
  getSheet().appendRow([ts, d.name||'', d.phone||'', d.email||'', d.city||'', d.source||'']);
}

// ── Email ──────────────────────────────────────────────────────
function sendMail(d) {
  var n  = d.name  || 'Unknown';
  var ph = d.phone || '—';
  var em = d.email || '';
  var ci = d.city  || '—';
  var ts = Utilities.formatDate(new Date(), 'Asia/Kolkata', 'dd MMM yyyy, hh:mm a');

  var html =
    '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto">' +
    '<div style="background:#06142E;padding:24px 28px;border-radius:10px 10px 0 0">' +
    '<h2 style="color:#fff;margin:0;font-size:18px">&#128204; New Enquiry — Master Analytic</h2>' +
    '<p style="color:rgba(255,255,255,.5);margin:6px 0 0;font-size:12px">' + ts + ' IST</p></div>' +
    '<div style="background:#fff;padding:24px 28px;border:1px solid #e5e7eb;border-top:none">' +
    field('Name',   n) +
    field('Phone',  '+91 ' + ph) +
    field('Email',  em) +
    field('City',   ci) +
    '</div>' +
    '<div style="background:#f3f4f6;padding:12px 28px;border-radius:0 0 10px 10px;font-size:11px;color:#9ca3af;border:1px solid #e5e7eb;border-top:none">Master Analytic — Automated lead alert</div>' +
    '</div>';

  MailApp.sendEmail({ to: NOTIFY_TO, subject: 'New Lead: ' + n + ' — Master Analytic', htmlBody: html, replyTo: em || NOTIFY_TO });

  // auto-reply to student
  if (em) {
    var reply =
      '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto">' +
      '<div style="background:#06142E;padding:24px 28px;border-radius:10px 10px 0 0;text-align:center">' +
      '<h2 style="color:#fff;margin:0;font-size:20px">&#127881; Thank You, ' + n + '!</h2>' +
      '<p style="color:rgba(255,255,255,.5);margin:8px 0 0;font-size:13px">Enquiry received</p></div>' +
      '<div style="background:#fff;padding:24px 28px;border:1px solid #e5e7eb;border-top:none;font-size:15px;color:#374151;line-height:1.7">' +
      '<p>Hi <strong>' + n + '</strong>, our counsellor will call you on <strong>+91 ' + ph + '</strong> within 24 hours.</p>' +
      '<div style="background:#eff6ff;border-left:4px solid #1447E6;padding:12px 16px;border-radius:6px;margin:16px 0">' +
      '<b>City:</b> ' + ci + '</div>' +
      '<a href="tel:+917428703467" style="display:inline-block;background:#1447E6;color:#fff;text-decoration:none;padding:11px 26px;border-radius:50px;font-weight:700;font-size:14px">Call: +91 74287 03467</a>' +
      '</div>' +
      '<div style="background:#f3f4f6;padding:12px 28px;border-radius:0 0 10px 10px;font-size:11px;color:#9ca3af;border:1px solid #e5e7eb;border-top:none">Master Analytic · Delhi &amp; Mumbai</div>' +
      '</div>';
    MailApp.sendEmail({ to: em, subject: 'Thank you — Master Analytic', htmlBody: reply, replyTo: NOTIFY_TO });
  }
}

function field(label, value) {
  return '<div style="margin-bottom:14px"><div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#1447E6;margin-bottom:3px">' + label + '</div>' +
    '<div style="font-size:14px;color:#111827;font-weight:600;padding:9px 12px;background:#f9fafb;border-left:3px solid #1447E6;border-radius:4px">' + value + '</div></div>';
}

// ── Handlers ───────────────────────────────────────────────────
function doGet(e) {
  var p = (e && e.parameter) ? e.parameter : {};

  if (!p.name) {
    return ContentService.createTextOutput('OK — Master Analytic lead script is live.')
      .setMimeType(ContentService.MimeType.TEXT);
  }

  var saved = false, mailed = false, saveErr = '', mailErr = '';
  try { saveRow(p); saved = true; } catch(err) { saveErr = err.message; }
  try { sendMail(p); mailed = true; } catch(err) { mailErr = err.message; }

  return ContentService.createTextOutput(
    JSON.stringify({ status: saved ? 'ok' : 'error', saved: saved, mailed: mailed, saveErr: saveErr, mailErr: mailErr })
  ).setMimeType(ContentService.MimeType.JSON);
}

function doPost(e) {
  var p = {};
  try {
    if (e.postData && e.postData.contents) p = JSON.parse(e.postData.contents);
  } catch(_) { p = (e && e.parameter) ? e.parameter : {}; }

  var saved = false, mailed = false, saveErr = '', mailErr = '';
  try { saveRow(p); saved = true; } catch(err) { saveErr = err.message; }
  try { sendMail(p); mailed = true; } catch(err) { mailErr = err.message; }

  return ContentService.createTextOutput(
    JSON.stringify({ status: saved ? 'ok' : 'error', saved: saved, mailed: mailed, saveErr: saveErr, mailErr: mailErr })
  ).setMimeType(ContentService.MimeType.JSON);
}

// ── Manual test — run inside Apps Script editor to verify ──────
function TEST_RUN() {
  saveRow({ name:'Test User', phone:'9876543210', email: NOTIFY_TO, city:'Delhi', source:'test' });
  sendMail({ name:'Test User', phone:'9876543210', email: NOTIFY_TO, city:'Delhi' });
  Logger.log('TEST DONE — check sheet + inbox');
}
