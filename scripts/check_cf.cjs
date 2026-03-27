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

console.log('=== CloudFront Distribution Status ===');
const status = runAws('cloudfront get-distribution --id E16JXL5MNZFIYK --query Distribution.Status --output json');
console.log('Status:', status);

console.log('\n=== Custom Error Responses ===');
const errors = runAws('cloudfront get-distribution --id E16JXL5MNZFIYK --query Distribution.DistributionConfig.CustomErrorResponses --output json');
console.log('Error Responses:', errors);

console.log('\n=== Aliases ===');
const aliases = runAws('cloudfront get-distribution --id E16JXL5MNZFIYK --query Distribution.DistributionConfig.Aliases --output json');
console.log('Aliases:', aliases);

console.log('\n=== ETag ===');
const etag = runAws('cloudfront get-distribution-config --id E16JXL5MNZFIYK --query ETag --output json');
console.log('ETag:', etag);
