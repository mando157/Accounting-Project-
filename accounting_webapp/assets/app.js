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
