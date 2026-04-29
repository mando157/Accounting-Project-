(function(){
  const codeOptions = Array.from(document.querySelectorAll('#account-codes option')).map(opt => ({
    code: opt.value.trim(),
    name: (opt.textContent || '').trim()
  }));

  function classify(code){
    if (!code) return '';
    if (code.startsWith('1')) return 'Asset';
    if (code.startsWith('2')) return 'Liability / Equity';
    if (code.startsWith('3')) return 'Expense';
    if (code.startsWith('4')) return 'Revenue';
    return 'Other';
  }

  function wireLine(line){
    const codeInput = line.querySelector('input[name="code[]"]');
    const nameInput = line.querySelector('.account-name-display');
    const typeInput = line.querySelector('.account-type-display');
    if (!codeInput) return;
    const sync = () => {
      const match = codeOptions.find(x => x.code === codeInput.value.trim());
      nameInput.value = match ? match.name : '';
      typeInput.value = match ? classify(match.code) : '';
    };
    codeInput.addEventListener('input', sync);
    sync();
  }

  document.querySelectorAll('.entry-line').forEach(wireLine);

  const addBtn = document.getElementById('add-line-btn');
  const linesWrap = document.getElementById('entry-lines');
  if (addBtn && linesWrap) {
    addBtn.addEventListener('click', () => {
      const first = linesWrap.querySelector('.entry-line');
      if (!first) return;
      const clone = first.cloneNode(true);
      clone.querySelectorAll('input').forEach(input => { input.value = ''; });
      linesWrap.appendChild(clone);
      wireLine(clone);
    });
  }
})();


(function(){
  const search = document.getElementById('account-search');
  const table = document.getElementById('accounts-table');
  if (!search || !table) return;
  const rows = Array.from(table.querySelectorAll('tbody tr'));
  const filter = () => {
    const q = search.value.trim().toLowerCase();
    rows.forEach(row => {
      const hit = !q || row.dataset.code.includes(q) || row.dataset.name.includes(q);
      row.style.display = hit ? '' : 'none';
    });
  };
  search.addEventListener('input', filter);
})();


(function(){
  let activeCodeInput = null;
  const lineWrap = document.getElementById('entry-lines');
  if (lineWrap) {
    const setActive = (input) => { activeCodeInput = input; };
    lineWrap.addEventListener('focusin', (e) => {
      if (e.target && e.target.name === 'code[]') setActive(e.target);
    });
    const search = document.getElementById('journal-account-search');
    const table = document.getElementById('journal-accounts-table');
    if (search && table) {
      const rows = Array.from(table.querySelectorAll('tbody tr'));
      const filter = () => {
        const q = search.value.trim().toLowerCase();
        rows.forEach(row => {
          const hit = !q || row.dataset.code.includes(q) || row.dataset.name.includes(q);
          row.style.display = hit ? '' : 'none';
        });
      };
      search.addEventListener('input', filter);
      table.addEventListener('click', (e) => {
        const btn = e.target.closest('.use-code-btn');
        if (!btn) return;
        const code = btn.dataset.code || '';
        if (!activeCodeInput) {
          activeCodeInput = lineWrap.querySelector('input[name="code[]"]');
        }
        if (activeCodeInput) {
          activeCodeInput.value = code;
          activeCodeInput.dispatchEvent(new Event('input', {bubbles:true}));
          activeCodeInput.focus();
        }
      });
    }
  }
})();

(function(){
  const form = document.getElementById('account-form');
  if (!form) return;
  const title = document.getElementById('account-form-title');
  const submitBtn = document.getElementById('account-form-submit');
  const cancelBtn = document.getElementById('account-form-cancel');
  const originalCode = document.getElementById('original-code');
  const codeInput = document.getElementById('account-code-input');
  const nameInput = document.getElementById('account-name-input');
  const groupInput = document.getElementById('account-group-input');
  const parentInput = document.getElementById('account-parent-input');
  const openingInput = document.getElementById('account-opening-input');
  const leafInput = document.getElementById('account-leaf-input');

  const resetForm = () => {
    form.action = 'actions/add_account.php';
    title.textContent = 'Add Account Code';
    submitBtn.textContent = '+ Add Account';
    cancelBtn.style.display = 'none';
    originalCode.value = '';
    codeInput.value = '';
    nameInput.value = '';
    groupInput.value = 'asset';
    parentInput.value = '';
    openingInput.value = '0';
    leafInput.checked = true;
  };

  document.querySelectorAll('.edit-account-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      form.action = 'actions/update_account.php';
      title.textContent = 'Edit Account Code';
      submitBtn.textContent = 'Save Changes';
      cancelBtn.style.display = 'inline-block';
      originalCode.value = btn.dataset.code || '';
      codeInput.value = btn.dataset.code || '';
      nameInput.value = btn.dataset.name || '';
      groupInput.value = btn.dataset.group || 'asset';
      parentInput.value = btn.dataset.parent || '';
      openingInput.value = btn.dataset.opening || '0';
      leafInput.checked = (btn.dataset.leaf || '0') === '1';
      window.scrollTo({top: 0, behavior: 'smooth'});
      codeInput.focus();
    });
  });

  cancelBtn?.addEventListener('click', resetForm);
})();
