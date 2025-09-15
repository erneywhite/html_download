<?php
// index.php — улучшенный листинг файлов из папки "files"
$dir = __DIR__ . DIRECTORY_SEPARATOR . 'files';
$webDir = 'files';
$files_meta = [];
if (is_dir($dir)) {
    foreach (scandir($dir) as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (is_file($path)) {
            $files_meta[] = [
                'name' => $name,
                'size' => filesize($path),
                'mtime' => filemtime($path),
                'type' => function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream'
            ];
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Файлы для скачивания</title>
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <style>
    :root{
      --bg-1:#06101a; --bg-2:#091425; --card:#071827; --muted:#9fb0c1; --accent:#56c1ff; --glass: rgba(255,255,255,0.03);
      --radius:12px; --glass-2: rgba(255,255,255,0.02);
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial; background: radial-gradient(ellipse at 20% 10%, rgba(86,193,255,0.02) 0%, transparent 10%), linear-gradient(180deg,var(--bg-1),var(--bg-2)); color:#e9f3fb}

    .container{max-width:1120px;margin:36px auto;padding:20px}

    header{display:flex;align-items:center;gap:16px;justify-content:space-between;margin-bottom:22px}
    .brand{display:flex;align-items:center;gap:14px}
    .logo{width:48px;height:48px;border-radius:10px;background:linear-gradient(135deg,var(--accent),#6dd1ff);display:flex;align-items:center;justify-content:center;color:#012; font-weight:700}
    h1{margin:0;font-size:20px}
    .subtitle{color:var(--muted);font-size:13px;margin-top:4px}

    .actions{display:flex;gap:10px;align-items:center}
    .search{background:var(--glass);border:1px solid rgba(255,255,255,0.03);padding:10px 12px;border-radius:10px;color:inherit;min-width:300px}
    .sort{background:transparent;border:1px solid rgba(255,255,255,0.03);padding:8px 10px;border-radius:10px;color:var(--muted)}

    .card{background: linear-gradient(180deg, rgba(255,255,255,0.015), rgba(255,255,255,0.01)); border-radius:var(--radius); padding:14px; box-shadow: 0 6px 30px rgba(2,8,23,0.6);}

    .count{color:var(--muted);font-size:14px;margin-bottom:12px}

    .list{display:grid;gap:10px}
    .row{display:flex;align-items:center;gap:14px;padding:12px;border-radius:10px;background:linear-gradient(90deg, rgba(255,255,255,0.01), rgba(255,255,255,0.007)); border:1px solid rgba(255,255,255,0.02);transition:transform .12s ease, background .12s ease}
    .row:hover{transform:translateY(-4px);background:linear-gradient(90deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01))}

    .thumb{width:56px;height:56px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--glass);font-weight:700;color:var(--accent);font-size:13px}
    .meta{flex:1;min-width:0}
    .filename{font-size:15px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .sub{font-size:13px;color:var(--muted);margin-top:6px}

    .btns{display:flex;align-items:center;gap:10px}
    .primary{background:linear-gradient(90deg,var(--accent),#7ad9ff);padding:10px 14px;border-radius:10px;border:none;color:#012;font-weight:700;text-decoration:none}
    .ghost{background:transparent;padding:9px 12px;border-radius:10px;border:1px solid rgba(255,255,255,0.04);color:var(--muted);cursor:pointer}

    /* dropdown */
    .dropdown{position:relative}
    .dd-menu{position:absolute;right:0;top:calc(100% + 10px);min-width:240px;background:#071225;border-radius:10px;padding:8px;box-shadow:0 8px 30px rgba(2,8,23,0.6);display:none;z-index:50}
    .dd-menu.show{display:block}
    .dd-item{display:block;padding:9px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:13px}
    .dd-item:hover{background:rgba(255,255,255,0.02);color:inherit}

    /* toast */
    .toast{position:fixed;right:20px;bottom:20px;background:#06202a;padding:12px 14px;border-radius:10px;color:#cfeefc;box-shadow:0 8px 30px rgba(2,8,23,0.6);display:none;z-index:200}
    .toast.show{display:block}

    @media (max-width:880px){.search{min-width:180px}.brand h1{font-size:18px}}
    @media (max-width:640px){.actions{flex-direction:column;align-items:stretch}.search{width:100%}}
  </style>
</head>
<body>
  <div class="container">
<header>
  <div class="brand">
    <div class="logo-wrapper">
      <img src="favicon.ico" alt="Logo" class="logo-img">
    </div>
    <div>
      <h1>Файлы для скачивания</h1>
    </div>
  </div>
  <div class="actions">
    <input id="search" class="search" placeholder="Поиск по имени..." />
    <select id="sort" class="sort">
      <option value="mtime_desc">По дате (новые сверху)</option>
      <option value="mtime_asc">По дате (старые сверху)</option>
      <option value="size_desc">По размеру (больше)</option>
      <option value="size_asc">По размеру (меньше)</option>
      <option value="name_asc">По имени (A→Z)</option>
      <option value="name_desc">По имени (Z→A)</option>
    </select>
  </div>
</header>

    <div class="card">
      <div id="count" class="count">Загружается...</div>
      <div id="list" class="list"></div>
    </div>
  </div>

  <div id="toast" class="toast"></div>

  <script>
    const FILES = <?php echo json_encode($files_meta, JSON_UNESCAPED_UNICODE); ?> || [];
    const webDir = '<?php echo addslashes($webDir); ?>';

    function humanSize(bytes){
      if(bytes===0) return '0 B';
      const thresh = 1024; const units = ['B','KB','MB','GB','TB']; let u=0; let n=bytes;
      while(n>=thresh && u<units.length-1){n/=thresh;u++;}
      return Math.round(n*10)/10 + ' ' + units[u];
    }
    function fmtDate(ts){ const d = new Date(ts*1000); return d.toLocaleString(); }

    const listEl = document.getElementById('list');
    const countEl = document.getElementById('count');
    const searchInput = document.getElementById('search');
    const sortSelect = document.getElementById('sort');
    const toast = document.getElementById('toast');

    function showToast(text){ toast.textContent = text; toast.classList.add('show'); clearTimeout(showToast._t); showToast._t = setTimeout(()=>toast.classList.remove('show'),1600); }

    function fileIcon(name){
      const ext = (name.split('.').pop()||'').toLowerCase();
      if(/(pdf|docx?|xlsx?|pptx?)/.test(ext)) return ext.toUpperCase();
      if(/(zip|rar|7z|tar|gz)/.test(ext)) return 'ZIP';
      if(/(jpe?g|png|gif|webp|svg)/.test(ext)) return 'IMG';
      if(/(html?|php|js|css|json|xml)/.test(ext)) return 'CODE';
      return ext.slice(0,4).toUpperCase();
    }

    function render(items){
      listEl.innerHTML = '';
      if(!items.length){countEl.textContent = 'Файлов не найдено.'; return}
      countEl.textContent = `Найдено ${items.length} файл(ов)`;
      for(const f of items){
        const row = document.createElement('div'); row.className='row';
        const thumb = document.createElement('div'); thumb.className='thumb'; thumb.textContent = fileIcon(f.name);
        const meta = document.createElement('div'); meta.className='meta';
        const name = document.createElement('div'); name.className='filename'; name.textContent = f.name;
        const sub = document.createElement('div'); sub.className='sub'; sub.textContent = `${humanSize(f.size)} • ${fmtDate(f.mtime)} • ${f.type||''}`;
        meta.appendChild(name); meta.appendChild(sub);

        const btns = document.createElement('div'); btns.className='btns';
        const dl = document.createElement('a'); dl.className='primary'; dl.textContent='Скачать'; dl.href = webDir + '/' + encodeURIComponent(f.name); dl.setAttribute('download',''); dl.target='_blank';

        const copyWrapper = document.createElement('div'); copyWrapper.style.display='flex'; copyWrapper.style.gap='8px';
        const copyBtn = document.createElement('button'); copyBtn.className='ghost'; copyBtn.textContent='Копировать';

        const dropdown = document.createElement('div'); dropdown.className='dropdown';
        const arrow = document.createElement('button'); arrow.className='ghost'; arrow.textContent='▾';
        const menu = document.createElement('div'); menu.className='dd-menu';
        const item1 = document.createElement('a'); item1.href='#'; item1.className='dd-item'; item1.textContent='Скопировать прямую ссылку';
        const item2 = document.createElement('a'); item2.href='#'; item2.className='dd-item'; item2.textContent='Скопировать команду для Linux (wget)';
        menu.appendChild(item1); menu.appendChild(item2);
        dropdown.appendChild(arrow); dropdown.appendChild(menu);

        const fileUrl = window.location.origin + '/' + webDir + '/' + encodeURIComponent(f.name);

        function copy(text){
          if(navigator.clipboard && navigator.clipboard.writeText){
            navigator.clipboard.writeText(text).then(()=>showToast('Скопировано в буфер'));
          } else {
            const t = document.createElement('textarea'); t.value = text; document.body.appendChild(t); t.select(); try{document.execCommand('copy'); showToast('Скопировано в буфер');}catch(e){showToast('Не удалось скопировать');} document.body.removeChild(t);
          }
        }

        copyBtn.addEventListener('click', ()=> copy(fileUrl));
        item1.addEventListener('click',(e)=>{ e.preventDefault(); copy(fileUrl); menu.classList.remove('show'); });
        item2.addEventListener('click',(e)=>{ e.preventDefault(); const wget = `wget -O "${f.name.replace(/\"/g,'\\"')}" "${fileUrl}"`; copy(wget); menu.classList.remove('show'); });

        arrow.addEventListener('click',(e)=>{ e.stopPropagation(); menu.classList.toggle('show'); });
        document.addEventListener('click', ()=>{ if(menu.classList.contains('show')) menu.classList.remove('show'); });

        copyWrapper.appendChild(copyBtn); copyWrapper.appendChild(dropdown);
        btns.appendChild(dl); btns.appendChild(copyWrapper);

        row.appendChild(thumb); row.appendChild(meta); row.appendChild(btns);
        listEl.appendChild(row);
      }
    }

    function apply(){
      const q = searchInput.value.trim().toLowerCase();
      let items = FILES.slice();
      if(q) items = items.filter(f=>f.name.toLowerCase().includes(q));
      const s = sortSelect.value;
      items.sort((a,b)=>{
        switch(s){
          case 'mtime_desc': return b.mtime - a.mtime;
          case 'mtime_asc': return a.mtime - b.mtime;
          case 'size_desc': return b.size - a.size;
          case 'size_asc': return a.size - b.size;
          case 'name_desc': return b.name.localeCompare(a.name);
          default: return a.name.localeCompare(b.name);
        }
      });
      render(items);
    }

    searchInput.addEventListener('input', apply);
    sortSelect.addEventListener('change', apply);
    apply();
  </script>
</body>
</html>
