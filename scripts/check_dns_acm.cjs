const { execSync } = require('child_process');

process.env.AWS_PAGER = '';

function runAws(args) {
  try {
    const result = execSync(`aws ${args}`, { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'] });
    return result.trim();
  } catch (e) {
    console.error('Error:', e.stderr || e.message);
    return null;
  }
}

console.log('=== Route53 Record Sets for agenchq.com ===');
const records = runAws('route53 list-resource-record-sets --hosted-zone-id Z09236092LH12WY229SBG --output json');
if (records) {
  const parsed = JSON.parse(records);
  parsed.ResourceRecordSets.forEach(r => {
    console.log(`${r.Name} [${r.Type}] -> ${r.AliasTarget?.DNSName || r.ResourceRecords?.map(rr => rr.Value).join(', ') || 'N/A'}`);
  });
}

console.log('\n=== ACM Certificates ===');
const certs = runAws('acm list-certificates --output json');
if (certs) {
  const parsed = JSON.parse(certs);
  parsed.CertificateSummaryList.forEach(c => {
    console.log(`Domain: ${c.DomainName}, ARN: ${c.CertificateArn}`);
  });
}
