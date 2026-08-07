<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('print-ticket', (event) => {
            setTimeout(() => {
                const html = event.html || (event[0] && event[0].html);
                if (html) {
                    printReceipt(html);
                } else {
                    console.error('Print ticket failed: No HTML content received', event);
                }
            }, 100);
        });
    });

    function printReceipt(htmlContent) {
        let iframe = document.getElementById('print-frame');
        if (!iframe) {
            iframe = document.createElement('iframe');
            iframe.id = 'print-frame';
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = '0';
            document.body.appendChild(iframe);
        }

        const blob = new Blob([htmlContent], {type: 'text/html'});
        const url = URL.createObjectURL(blob);

        iframe.onload = () => {
            URL.revokeObjectURL(url);
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) {
                console.error('Printing failed:', e);
            }
        };

        iframe.src = url;
    }
</script>