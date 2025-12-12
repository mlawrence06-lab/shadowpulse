const https = require('https');

const data = JSON.stringify({
  member_uuid: 'test-uuid-verifier',
  page_views: 1
});

const options = {
  hostname: 'vod.fan',
  path: '/shadowpulse/api/v1/update_member_stats.php',
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Content-Length': data.length
  }
};

const req = https.request(options, (res) => {
  console.log(`STATUS: ${res.statusCode}`);
  res.on('data', (d) => {
    process.stdout.write(d);
  });
});

req.on('error', (e) => {
  console.error(e);
});

req.write(data);
req.end();
