/**
 (Balance Sheet)
  تعتمد هذه الصفحة على البيانات المالية القادمة من الصفحات السابقة
 (Journal Entries) و(Trial Balance).
  يتم حساب الإجماليات تلقائي
الأصول = الديون + حقوق الملكية
 */

/* --إ البيانات الأولية
تم تصفير جميع القيم المالية لتبدأ من الصفر  */

const defaultBalanceSheetData = {
    header: {
        brandName: "Fiscal Curator",
        courseName: "Accounting 101",
        logoIcon: "fas fa-building-columns"
    },
    pageTitle: "Balance Sheet",
    asOfDate: "December 31, 2023",
    summary: {
        assetTrend: "+0.0% from last period",
        inBalance: true
    },
    // الأصول (Assets)
    assets: {
        currentAssets: [
            { label: "Cash and Cash Equivalents", value: 0.00 },
            { label: "Accounts Receivable", value: 0.00 },
            { label: "Inventory", value: 0.00 }
        ],
        fixedAssets: [
            { label: "Property & Equipment", value: 0.00 },
            { label: "Less: Accumulated Depreciation", value: 0.00, isNegative: true }
        ]
    },
    // الديون وحقوق الملكية (Liabilities & Equity)
    liabilitiesEquity: {
        liabilities: [
            { label: "Accounts Payable", value: 0.00 },
            { label: "Short-term Debt", value: 0.00 },
            { label: "Long-term Notes Payable", value: 0.00 }
        ],
        equity: [
            { label: "Common Stock", value: 0.00 },
            { label: "Retained Earnings", value: 0.00 }
        ]
    }
};

/* --(Calculation Engine) --- */
function calculateFinancialTotals(data) {
    // حساب إجمالي الأصول ة
    const totalCurrentAssets = data.assets.currentAssets.reduce((sum, item) => sum + item.value, 0);
    
    // حساب إجمالي الأصول الثابتة (مع مراعاة القيم السالبة)
    const totalFixedAssets = data.assets.fixedAssets.reduce((sum, item) => sum + item.value, 0);
    
    // إجمالي الأصول النهائي
    const grandTotalAssets = totalCurrentAssets + totalFixedAssets;

    // حساب إجمالي liabilities
    const totalLiabilities = data.liabilitiesEquity.liabilities.reduce((sum, item) => sum + item.value, 0);
    
    // حساب إجمالي حقوق الملكية
    const totalEquity = data.liabilitiesEquity.equity.reduce((sum, item) => sum + item.value, 0);
    
    // liabilities اجمالي  حقوق الملكية النهائي و
    const grandTotalLiabilitiesEquity = totalLiabilities + totalEquity;

    return {
        totalCurrentAssets,
        totalFixedAssets,
        grandTotalAssets,
        totalLiabilities,
        totalEquity,
        grandTotalLiabilitiesEquity
    };
}

function formatCurrency(amount) {
    const formatted = Math.abs(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return amount < 0 ? `($${formatted})` : `$${formatted}`;
}


/*
   يارب تطلع صح 
إنشاء صفوف الجداول ديناميكياً.
  فى مشكلة هنا */
function renderTableRows(containerId, items) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    container.innerHTML = ''; 
    
    items.forEach(item => {
        const row = document.createElement('div');
        row.className = 'table-row';
        
        const labelSpan = document.createElement('span');
        labelSpan.className = 'label';
        labelSpan.textContent = item.label;
        
        const valueSpan = document.createElement('span');
        valueSpan.className = 'value';
        
        // تمييز القيم السالبة باللون الأحمر
        if (item.isNegative || item.value < 0) {
            valueSpan.classList.add('text-danger');
        }
        
        valueSpan.textContent = formatCurrency(item.value);
        
        row.appendChild(labelSpan);
        row.appendChild(valueSpan);
        container.appendChild(row);
    });
}

/**
 * الدالة الأساسية لتحديث كافة عناصر الصفحة بالبيانات الجديدة.
 */
function renderBalanceSheet() {
    // استرجاع البيانات من localStorage أو استخدام البيانات الافتراضية
    const storedData = localStorage.getItem('balanceSheetData');
    const data = storedData ? JSON.parse(storedData) : defaultBalanceSheetData;
    
    // تنفيذ الحسابات التلقائية
    const totals = calculateFinancialTotals(data);

    //  تحديث الهيدر ومعلومات الصفحة
    document.getElementById('header-brand-name').textContent = data.header.brandName;
    document.getElementById('header-course-name').textContent = data.header.courseName;
    document.getElementById('header-logo-icon').className = data.header.logoIcon + " me-2";
    document.getElementById('as-of-date').textContent = data.asOfDate;

    const assetParts = totals.grandTotalAssets.toLocaleString('en-US', { minimumFractionDigits: 2 }).split('.');
    const liabParts = totals.grandTotalLiabilitiesEquity.toLocaleString('en-US', { minimumFractionDigits: 2 }).split('.');
    
    document.getElementById('total-assets-value').textContent = assetParts[0];
    document.getElementById('total-liabilities-equity-value').textContent = liabParts[0];
    document.getElementById('asset-trend-text').textContent = data.summary.assetTrend;

    //  عرض جداول الأصول والاجمالى ا
    renderTableRows('current-assets-list', data.assets.currentAssets);
    renderTableRows('fixed-assets-list', data.assets.fixedAssets);
    document.getElementById('total-current-assets').textContent = formatCurrency(totals.totalCurrentAssets);
    document.getElementById('total-fixed-assets').textContent = formatCurrency(totals.totalFixedAssets);
    document.getElementById('grand-total-assets').textContent = formatCurrency(totals.grandTotalAssets);

    //  عرض جداول الخصوم وحقوق الملكية وإجمالياتها
    renderTableRows('liabilities-list', data.liabilitiesEquity.liabilities);
    renderTableRows('equity-list', data.liabilitiesEquity.equity);
    document.getElementById('total-liabilities').textContent = formatCurrency(totals.totalLiabilities);
    document.getElementById('total-equity').textContent = formatCurrency(totals.totalEquity);
    document.getElementById('grand-total-liabilities-equity').textContent = formatCurrency(totals.grandTotalLiabilitiesEquity);

    //  التحقق من التوازن هام(Assets = Liabilities + Equity)
    const isInBalance = Math.abs(totals.grandTotalAssets - totals.grandTotalLiabilitiesEquity) < 0.01;
    const statusBadge = document.querySelector('.status .badge');
    if (isInBalance) {
        statusBadge.className = "badge bg-success-subtle text-success px-2 py-1";
        statusBadge.innerHTML = '<i class="fas fa-circle small me-1"></i> IN BALANCE';
    } else {
        statusBadge.className = "badge bg-danger-subtle text-danger px-2 py-1";
        statusBadge.innerHTML = '<i class="fas fa-circle small me-1"></i> OUT OF BALANCE';
    }
}


document.addEventListener('DOMContentLoaded', () => {
    // تصفير البيانات في كل مرة يتم فيها تحميل الصفحة 
    // localStorage
    localStorage.setItem('balanceSheetData', JSON.stringify(defaultBalanceSheetData));
    
    renderBalanceSheet();
    console.log("Balance Sheet Initialized with Zero Data and Auto-Calculations.");
});
