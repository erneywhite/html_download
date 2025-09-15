<?php
// index.php — улучшенный листинг файлов и подпапок из папки "files" (scroll + folder icons)
$dir = __DIR__ . DIRECTORY_SEPARATOR . 'files';
$webDir = 'files';
$items = [];

if (is_dir($dir)) {
    foreach (scandir($dir) as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (is_dir($path)) {
            // соберём файлы внутри подкаталога (только файлы, не рекурсивно)
            $children = [];
            foreach (scandir($path) as $c) {
                if ($c === '.' || $c === '..') continue;
                $cp = $path . DIRECTORY_SEPARATOR . $c;
                if (is_file($cp)) {
                    $children[] = [
                        'name' => $c,
                        'size' => filesize($cp),
                        'mtime' => filemtime($cp),
                        'type' => function_exists('mime_content_type') ? mime_content_type($cp) : 'application/octet-stream'
                    ];
                }
            }
            $items[] = [
                'name' => $name,
                'type' => 'dir',
                'children' => $children
            ];
        } elseif (is_file($path)) {
            $items[] = [
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
        :root {
            --bg-1:#06101a;
            --bg-2:#091425;
            --card:#071827;
            --muted:#9fb0c1;
            --accent:#56c1ff;
            --accent-dark:#2ea3dc;
            --glass:rgba(255,255,255,0.03);
            --radius:12px;
            --glass-2: rgba(255,255,255,0.02);
        }
        *{box-sizing:border-box}
        html,body{height:100%;margin:0;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial}
        body{background:radial-gradient(ellipse at 20% 10%, rgba(86,193,255,0.02) 0%, transparent 10%),linear-gradient(180deg,var(--bg-1),var(--bg-2));color:#e9f3fb}
        .container{max-width:1120px;margin:36px auto;padding:20px;display:flex;flex-direction:column;height:calc(100vh - 72px)}
        header{display:flex;align-items:center;gap:16px;justify-content:space-between;margin-bottom:22px}
        .brand{display:flex;align-items:center;gap:14px}
        .logo-img{width:48px;height:48px;border-radius:10px}
        h1{margin:0;font-size:20px}
        .actions{display:flex;gap:10px;align-items:center}
        .search{background:var(--glass);border:1px solid rgba(255,255,255,0.03);padding:10px 12px;border-radius:10px;color:inherit;min-width:300px}
        .sort{background:transparent;border:1px solid rgba(255,255,255,0.03);padding:8px 10px;border-radius:10px;color:var(--muted)}
        .card{background:linear-gradient(180deg, rgba(255,255,255,0.015), rgba(255,255,255,0.01));border-radius:var(--radius);padding:14px;box-shadow:0 6px 30px rgba(2,8,23,0.6);flex:1 1 auto;overflow-y:auto}
        /* custom scrollbar styled to match palette */
        .card::-webkit-scrollbar{width:10px;height:10px}
        .card::-webkit-scrollbar-track{background:transparent}
        .card::-webkit-scrollbar-thumb{
            background: linear-gradient(180deg, rgba(86,193,255,0.22), rgba(86,193,255,0.12));
            border-radius: 10px;
            border: 2px solid transparent;
            background-clip: padding-box;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.02);
        }
        /* Firefox */
        .card{scrollbar-width:thin;scrollbar-color: rgba(86,193,255,0.3) transparent;}
        .count{color:var(--muted);font-size:14px;margin-bottom:12px}
        .list{display:grid;gap:10px}
        .row{display:flex;align-items:center;gap:14px;padding:12px;border-radius:10px;background:linear-gradient(90deg, rgba(255,255,255,0.01), rgba(255,255,255,0.007));border:1px solid rgba(255,255,255,0.02);transition:transform .12s ease, background .12s ease}
        .row:hover{transform:translateY(-4px);background:linear-gradient(90deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01))}
        .dir-row{cursor:pointer;background:linear-gradient(90deg, rgba(255,255,255,0.015), rgba(255,255,255,0.007))}
        .thumb{width:56px;height:56px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:var(--glass);font-weight:700;color:var(--accent);font-size:13px}
        /* folder-specific thumb */
        .thumb.folder{ background: linear-gradient(135deg, var(--accent), #7ad9ff); color: #012; font-size:20px; box-shadow: 0 4px 18px rgba(86,193,255,0.08); }
        .meta{flex:1;min-width:0}
        .filename{font-size:15px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .sub{font-size:13px;color:var(--muted);margin-top:6px}
        .btns{display:flex;align-items:center;gap:10px}
        .primary{background:linear-gradient(90deg,var(--accent),#7ad9ff);padding:10px 14px;border-radius:10px;border:none;color:#012;font-weight:700;text-decoration:none}
        .ghost{background:transparent;padding:9px 12px;border-radius:10px;border:1px solid rgba(255,255,255,0.04);color:var(--muted);cursor:pointer}
        .copy-group{position:relative;display:inline-flex;align-items:center}
        .copy-main{display:inline-flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;background:rgba(255,255,255,0.08);border:none;color:#e6eef6;cursor:pointer}
        .dd-menu{display:none;position:absolute;right:0;top:calc(100% + 8px);min-width:240px;background:#071225;border-radius:10px;padding:8px;box-shadow:0 8px 30px rgba(2,8,23,0.6);z-index:50}
        .dd-menu.show{display:block}
        .dd-item{display:block;padding:9px;border-radius:8px;color:var(--muted);text-decoration:none;font-size:13px}
        .dd-item:hover{background:rgba(255,255,255,0.02);color:inherit}
        .children{margin-top:8px;margin-left:70px;display:grid;gap:8px;overflow:hidden;max-height:0;opacity:0;transition:max-height 320ms cubic-bezier(.2,.9,.2,1),opacity 220ms ease}
        .children.open{opacity:1}
        .children .row{margin:0}
        .indent{padding-left:10px}
        .toggle-arrow{display:inline-block;transition:transform 220ms ease}
        .toggle-arrow.expanded{transform:rotate(180deg)}
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
                <img src="favicon.ico" alt="Logo" class="logo-img">
                <h1>Файлы для скачивания</h1>
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
        const FILES = <?php echo json_encode($items, JSON_UNESCAPED_UNICODE); ?> || [];
        const webDir = '<?php echo addslashes($webDir); ?>';

        function humanSize(bytes){if(bytes===0)return'0 B';const thresh=1024;const units=['B','KB','MB','GB','TB'];let u=0;let n=bytes;while(n>=thresh&&u<units.length-1){n/=thresh;u++;}return Math.round(n*10)/10+' '+units[u];}
        function fmtDate(ts){const d=new Date(ts*1000);return d.toLocaleString();}
        function fileIcon(name){
            const ext=(name.split('.').pop()||'').toLowerCase();
            if (ext === 'iso') return '💿';
            if (/(pdf|docx?|xlsx?|pptx?)/.test(ext)) return ext.toUpperCase();
            if (/(zip|rar|7z|tar|gz)/.test(ext)) return 'ZIP';
            if (/(jpe?g|png|gif|webp|svg)/.test(ext)) return 'IMG';
            if (/(html?|php|js|css|json|xml)/.test(ext)) return 'CODE';
            return ext.slice(0,4).toUpperCase();
        }
        function copyToClipboard(text){if(navigator.clipboard){navigator.clipboard.writeText(text).then(()=>showToast('Скопировано в буфер'))}else{const t=document.createElement('textarea');t.value=text;document.body.appendChild(t);t.select();try{document.execCommand('copy');showToast('Скопировано в буфер');}catch(e){showToast('Не удалось скопировать');}document.body.removeChild(t);}}

        const listEl=document.getElementById('list'),countEl=document.getElementById('count'),searchInput=document.getElementById('search'),sortSelect=document.getElementById('sort'),toast=document.getElementById('toast');

        function showToast(t){toast.textContent=t;toast.classList.add('show');clearTimeout(showToast._t);showToast._t=setTimeout(()=>toast.classList.remove('show'),1600);}

        function animateOpen(el){
            el.style.display = 'grid';
            requestAnimationFrame(()=>{
                const h = el.scrollHeight;
                el.style.maxHeight = h + 'px';
                el.classList.add('open');
            });
        }
        function animateClose(el){
            el.style.maxHeight = el.scrollHeight + 'px';
            requestAnimationFrame(()=>{
                el.style.maxHeight = '0px';
                el.classList.remove('open');
            });
            const onEnd = function(){
                if (el.style.maxHeight === '0px') el.style.display = 'none';
                el.removeEventListener('transitionend', onEnd);
            };
            el.addEventListener('transitionend', onEnd);
        }

        function render(items){
            listEl.innerHTML='';
            const dirs = items.filter(i=>i.type==='dir');
            const files = items.filter(i=>i.type!=='dir');
            const total = files.length + dirs.length;
            if(total===0){countEl.textContent='Файлов не найдено.';return;}countEl.textContent=`Найдено ${total} элемент(ов)`;

            dirs.forEach(dir=>{
                const row=document.createElement('div');row.className='row dir-row';
                const thumb=document.createElement('div');thumb.className='thumb folder';thumb.textContent='📁';
                const meta=document.createElement('div');meta.className='meta';
                const name=document.createElement('div');name.className='filename';name.textContent=dir.name;
                const sub=document.createElement('div');sub.className='sub';sub.textContent=`Папка • ${dir.children.length} файл(ов)`;
                meta.appendChild(name);meta.appendChild(sub);

                const btns=document.createElement('div');btns.className='btns';
                const toggle=document.createElement('button');toggle.className='ghost';toggle.innerHTML = '<span class="toggle-arrow">▾</span>';
                toggle.setAttribute('aria-expanded','false');
                btns.appendChild(toggle);
                row.appendChild(thumb);row.appendChild(meta);row.appendChild(btns);
                listEl.appendChild(row);

                const childrenWrap=document.createElement('div');childrenWrap.className='children';childrenWrap.style.display='none';childrenWrap.style.maxHeight='0px';
                dir.children.forEach(f=>{
                    const crow=document.createElement('div');crow.className='row indent';
                    const cthumb=document.createElement('div');cthumb.className='thumb';cthumb.textContent=fileIcon(f.name);
                    const cmeta=document.createElement('div');cmeta.className='meta';
                    const cname=document.createElement('div');cname.className='filename';cname.textContent=f.name;
                    const csub=document.createElement('div');csub.className='sub';csub.textContent=`${humanSize(f.size)} • ${fmtDate(f.mtime)} • ${f.type||''}`;
                    cmeta.appendChild(cname);cmeta.appendChild(csub);

                    const cbtns=document.createElement('div');cbtns.className='btns';
                    const cdl=document.createElement('a');cdl.className='primary';cdl.textContent='Скачать';cdl.href=webDir+'/'+encodeURIComponent(dir.name)+'/'+encodeURIComponent(f.name);cdl.setAttribute('download','');cdl.target='_blank';

                    const ccopy=document.createElement('button');ccopy.className='ghost';ccopy.textContent='Копировать ▾';
                    const cmenu=document.createElement('div');cmenu.className='dd-menu';
                    const citem1=document.createElement('a');citem1.href='#';citem1.className='dd-item';citem1.textContent='Скопировать прямую ссылку';
                    const citem2=document.createElement('a');citem2.href='#';citem2.className='dd-item';citem2.textContent='Скопировать команду для Linux (wget)';
                    cmenu.appendChild(citem1);cmenu.appendChild(citem2);

                    const childUrl=window.location.origin+'/'+webDir+'/'+encodeURIComponent(dir.name)+'/'+encodeURIComponent(f.name);
                    ccopy.addEventListener('click',e=>{e.stopPropagation();cmenu.classList.toggle('show');});
                    citem1.addEventListener('click',e=>{e.preventDefault();copyToClipboard(childUrl);cmenu.classList.remove('show');});
                    citem2.addEventListener('click',e=>{e.preventDefault();const wget=`wget -O \"${f.name.replace(/\"/g,'\\\\\"')}\" \"${childUrl}\"`;copyToClipboard(wget);cmenu.classList.remove('show');});

                    cbtns.appendChild(cdl);cbtns.appendChild(ccopy);crow.appendChild(cthumb);crow.appendChild(cmeta);crow.appendChild(cbtns);crow.appendChild(cmenu);
                    childrenWrap.appendChild(crow);
                });

                listEl.appendChild(childrenWrap);

                // открыть/закрыть по клику на всю строку (кроме кликов по кнопкам внутри .btns)
                row.addEventListener('click', function(e){
                    if (e.target.closest('.btns')) return;
                    const isOpen = childrenWrap.classList.contains('open');
                    if(isOpen){
                        toggle.querySelector('.toggle-arrow').classList.remove('expanded');
                        animateClose(childrenWrap);
                        childrenWrap.classList.remove('open');
                        toggle.setAttribute('aria-expanded','false');
                    } else {
                        toggle.querySelector('.toggle-arrow').classList.add('expanded');
                        animateOpen(childrenWrap);
                        childrenWrap.classList.add('open');
                        toggle.setAttribute('aria-expanded','true');
                    }
                });

            });

            files.forEach(f=>{
                const row=document.createElement('div');row.className='row';
                const thumb=document.createElement('div');thumb.className='thumb';thumb.textContent=fileIcon(f.name);
                const meta=document.createElement('div');meta.className='meta';
                const name=document.createElement('div');name.className='filename';name.textContent=f.name;
                const sub=document.createElement('div');sub.className='sub';sub.textContent=`${humanSize(f.size)} • ${fmtDate(f.mtime)} • ${f.type||''}`;
                meta.appendChild(name);meta.appendChild(sub);

                const btns=document.createElement('div');btns.className='btns';
                const dl=document.createElement('a');dl.className='primary';dl.textContent='Скачать';dl.href=webDir+'/'+encodeURIComponent(f.name);dl.setAttribute('download','');dl.target='_blank';
                const copyBtn=document.createElement('button');copyBtn.className='ghost';copyBtn.textContent='Копировать ▾';
                const menu=document.createElement('div');menu.className='dd-menu';
                const item1=document.createElement('a');item1.href='#';item1.className='dd-item';item1.textContent='Скопировать прямую ссылку';
                const item2=document.createElement('a');item2.href='#';item2.className='dd-item';item2.textContent='Скопировать команду для Linux (wget)';
                menu.appendChild(item1);menu.appendChild(item2);

                const fileUrl=window.location.origin+'/'+webDir+'/'+encodeURIComponent(f.name);
                copyBtn.addEventListener('click',e=>{e.stopPropagation();menu.classList.toggle('show');});
                item1.addEventListener('click',e=>{e.preventDefault();copyToClipboard(fileUrl);menu.classList.remove('show');});
                item2.addEventListener('click',e=>{e.preventDefault();const wget=`wget -O \"${f.name.replace(/\"/g,'\\\\\"')}\" \"${fileUrl}\"`;copyToClipboard(wget);menu.classList.remove('show');});

                btns.appendChild(dl);btns.appendChild(copyBtn);row.appendChild(thumb);row.appendChild(meta);row.appendChild(btns);row.appendChild(menu);listEl.appendChild(row);
            });
        }

        // close open menus when clicking outside
        document.addEventListener('click',function(e){ if(!e.target.closest('.copy-group') && !e.target.classList.contains('ghost')){document.querySelectorAll('.dd-menu.show').forEach(m=>m.classList.remove('show'));}});

        function apply(){
            const q=(searchInput.value||'').trim().toLowerCase();
            let items=Array.isArray(FILES)?FILES.slice():[];
            if(q){
                items = items.map(it=>{
                    if(it.type==='dir'){
                        const filteredChildren = (it.children||[]).filter(c=>c.name.toLowerCase().includes(q));
                        return Object.assign({}, it, { children: filteredChildren });
                    }
                    return it.name.toLowerCase().includes(q)?it:null;
                }).filter(Boolean);
            }
            const s=sortSelect.value;
            items.forEach(it=>{
                if(it.type==='dir' && Array.isArray(it.children)){
                    it.children.sort((a,b)=>{
                        switch(s){
                            case 'mtime_desc': return b.mtime - a.mtime;
                            case 'mtime_asc': return a.mtime - b.mtime;
                            case 'size_desc': return b.size - a.size;
                            case 'size_asc': return a.size - b.size;
                            case 'name_desc': return b.name.localeCompare(a.name);
                            default: return a.name.localeCompare(b.name);
                        }
                    });
                }
            });
            items.sort((a,b)=>{
                if(a.type==='dir' && b.type!=='dir') return -1;
                if(b.type==='dir' && a.type!=='dir') return 1;
                const aa = a.type==='dir'?a.name:a;
                const bb = b.type==='dir'?b.name:b;
                if(s==='name_asc' || s==='name_desc'){
                    return (s==='name_desc' ? bb.name.localeCompare(aa.name) : aa.name.localeCompare(bb.name));
                }
                if(s==='mtime_desc' || s==='mtime_asc' || s==='size_desc' || s==='size_asc'){
                    if(a.type==='dir' && b.type==='dir') return a.name.localeCompare(b.name);
                    if(a.type!=='dir' && b.type!=='dir'){
                        switch(s){
                            case 'mtime_desc': return b.mtime - a.mtime;
                            case 'mtime_asc': return a.mtime - b.mtime;
                            case 'size_desc': return b.size - a.size;
                            case 'size_asc': return a.size - b.size;
                        }
                    }
                }
                return 0;
            });

            render(items);
        }

        searchInput.addEventListener('input',apply);
        sortSelect.addEventListener('change',apply);
        apply();
    </script>
</body>
</html>
