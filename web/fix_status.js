import fs from 'fs';

function updateJson(file, oldKeyStr, newKeyStr) {
  try {
    let content = fs.readFileSync(file, 'utf8');
    content = content.replace(oldKeyStr, newKeyStr);
    fs.writeFileSync(file, content);
  } catch(e) {}
}

updateJson('c:\\code\\iosu\\mus\\web\\src\\i18n\\locales\\es.json', '"in_progress": "En Juego"', '"pending": "Pendiente"');
updateJson('c:\\code\\iosu\\mus\\web\\src\\i18n\\locales\\en.json', '"in_progress": "In Progress"', '"pending": "Pending"');
updateJson('c:\\code\\iosu\\mus\\web\\src\\i18n\\locales\\eu.json', '"in_progress": "Jokoan"', '"pending": "Zain"');

const vueFile = 'c:\\code\\iosu\\mus\\web\\src\\views\\TournamentView.vue';
let vueContent = fs.readFileSync(vueFile, 'utf8');
vueContent = vueContent.replace(
  "m.score1 >= data.rulePoints || m.score2 >= data.rulePoints ? t('tournament_view.match_status.finished') : t('tournament_view.match_status.in_progress')",
  "m.score1 > 0 || m.score2 > 0 ? t('tournament_view.match_status.finished') : t('tournament_view.match_status.pending')"
);
vueContent = vueContent.replace(
  "match.status === t('tournament_view.match_status.in_progress') ? 'success' : 'secondary'",
  "match.status === t('tournament_view.match_status.finished') ? 'success' : 'secondary'"
);
fs.writeFileSync(vueFile, vueContent);

console.log('Done');
