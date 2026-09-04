class BaseChart {
    constructor(selector, customOptions = {}) {
        this.target = typeof selector === 'string' ? document.querySelector(selector) : selector;
        this.chartInstance = null;

        const chartType = customOptions.chart?.type || 'bar';

        this.defaultOptions = {
            chart: {
                type: chartType,
                height: 350,
                toolbar: { show: false },
                animations: { enabled: false }
            },
            series: [], // <-- THÊM DÒNG NÀY để không bị undefined lúc khởi tạo
            dataLabels: { enabled: false },
            tooltip: { theme: 'light' },
            noData: { text: 'Không có dữ liệu' }
        };

        // Tự động tinh chỉnh cấu hình theo nhóm biểu đồ
        if (!['pie', 'donut', 'radialBar'].includes(chartType)) {
            this.defaultOptions.grid = { borderColor: '#e0e0e0', strokeDashArray: 4 };
            if (['line', 'area'].includes(chartType)) {
                this.defaultOptions.stroke = { curve: 'smooth', width: 2 };
            }
        }

        this.options = this.mergeDeep(this.defaultOptions, customOptions);
    }

    render() {
        if (!this.target) return null;
        this.chartInstance = new ApexCharts(this.target, this.options);
        return this.chartInstance.render();
    }

    /**
     * Đẩy dữ liệu vào biểu đồ (nhận từ module Fetch bên ngoài)
     * @param {Object} data - Dữ liệu đã fetch xong
     */
    setData(data) {
        if (!this.chartInstance || !data) return;

        const currentType = this.options.chart.type;

        if (['pie', 'donut', 'radialBar'].includes(currentType)) {
            this.chartInstance.updateOptions({
                labels: data.labels || []
            }, false, true);
            this.chartInstance.updateSeries(data.series || [], true);
        } else {
            this.chartInstance.updateOptions({
                xaxis: { categories: data.categories || [] }
            }, false, true);
            this.chartInstance.updateSeries(data.series || [], true);
        }
    }
    destroy() {
        if (this.chartInstance) {
            this.chartInstance.destroy();
            this.chartInstance = null;
        }
    }

    mergeDeep(target, source) {
        const output = { ...target };
        if (this.isObject(target) && this.isObject(source)) {
            Object.keys(source).forEach(key => {
                if (this.isObject(source[key])) {
                    if (!(key in target)) Object.assign(output, { [key]: source[key] });
                    else output[key] = this.mergeDeep(target[key], source[key]);
                } else {
                    Object.assign(output, { [key]: source[key] });
                }
            });
        }
        return output;
    }

    isObject(item) {
        return item && typeof item === 'object' && !Array.isArray(item);
    }
}