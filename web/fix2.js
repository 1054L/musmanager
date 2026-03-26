import fs from 'fs';
const file = 'c:\\code\\iosu\\mus\\web\\src\\views\\TournamentView.vue';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(/stage:\s*m\.stage,\s*time:\s*'12:00'\s*}\)\)/g, 'stage: m.stage\n      }))');

fs.writeFileSync(file, content);
console.log('Fixed');
