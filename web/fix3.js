import fs from 'fs';
const file = 'c:\\code\\iosu\\mus\\web\\src\\views\\TournamentView.vue';
let content = fs.readFileSync(file, 'utf8');

content = content.replace(
  '(stageMatches, stage) in groupedMatches',
  '([stage, stageMatches]) in groupedMatches'
);

fs.writeFileSync(file, content);
console.log('Fixed loop');
