const puppeteer = require('puppeteer');
const path = require('path');

(async () => {
    try {
        console.log('Starting puppeteer...');
        const browser = await puppeteer.launch();
        const page = await browser.newPage();
        
        const htmlPath = 'file:///' + path.resolve('brochure.html').replace(/\\/g, '/');
        console.log('Navigating to ' + htmlPath);
        
        await page.goto(htmlPath, { waitUntil: 'networkidle0' });
        
        console.log('Generating PDF...');
        await page.pdf({
            path: 'brosur_finance_blu.pdf',
            format: 'A4',
            printBackground: true,
            margin: {
                top: '0',
                right: '0',
                bottom: '0',
                left: '0'
            }
        });
        
        console.log('PDF generated successfully!');
        await browser.close();
    } catch (error) {
        console.error('Error generating PDF:', error);
    }
})();
