document.addEventListener('DOMContentLoaded', () => {
  const bpeWpImportedFrom = document.querySelectorAll('[data-bpe-wp-import]');
  bpeWpImportedFrom.forEach(el => {
    const src = el.getAttribute('data-bpe-wp-import');
    const script = document.createElement('script');
    script.src = src;
    el.appendChild(script);
    const formId = el.children[0].getAttribute('id');
    setTimeout(()=>{
      const form = document.getElementById(formId);
      if (form.classList.contains('bpe-wp-loader-container')) {
        form.innerHTML = '<p style="text-align: center; padding: 20px 10px 20px 10px;">An error occured. Please try again later</p>';
      }
    },5000);
  });
});