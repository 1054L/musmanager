import { build } from 'vite';
try {
  await build();
} catch (e) {
  import('fs').then(fs => fs.writeFileSync('error.json', JSON.stringify(e, Object.getOwnPropertyNames(e), 2)));
}
