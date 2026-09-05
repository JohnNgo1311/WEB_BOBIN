class BaseChart {
    /**
     * Khởi tạo một đối tượng biểu đồ (Wrapper Wrapper cho thư viện ApexCharts)
     * Thiết kế theo dạng class giúp dễ dàng khởi tạo nhiều biểu đồ trên cùng một trang.
     * 
     * @param {string|HTMLElement} selector - CSS Selector (vd: '#myChart') hoặc DOM Element để gắn biểu đồ
     * @param {Object} customOptions - Các cấu hình ApexCharts tùy chỉnh riêng cho biểu đồ này
     */
    constructor(selector, customOptions = {}) {
        // 1. Xác định phần tử DOM sẽ chứa biểu đồ[cite: 6]
        this.target = typeof selector === 'string' ? document.querySelector(selector) : selector;
        this.chartInstance = null;

        // 2. Lấy loại biểu đồ từ customOptions, mặc định là biểu đồ cột dọc ('bar')[cite: 6]
        const chartType = customOptions.chart?.type || 'bar';

        // 3. Cấu hình cốt lõi mặc định chung cho toàn hệ thống
        // Điều này giúp bạn không phải viết lại những cấu hình nhàm chán ở mọi trang[cite: 6].
        this.defaultOptions = {
            chart: {
                type: chartType,
                height: 350,
                toolbar: { show: false },      // Ẩn menu tải xuống (hamburger menu) cho UI gọn gàng[cite: 6]
                animations: { enabled: false } // Mặc định tắt animation để tối ưu hiệu năng render lần đầu[cite: 6]
            },
            series: [],                        // Khởi tạo mảng rỗng để ApexCharts không báo lỗi undefined[cite: 6]
            dataLabels: { enabled: false },    // Mặc định tắt nhãn dữ liệu nổi trên cột/đường[cite: 6]
            tooltip: { theme: 'light' },       // Chế độ màu nền của tooltip khi di chuột vào[cite: 6]
            noData: {
                text: 'Không có dữ liệu',      // Text hiển thị tự động khi mảng series rỗng[cite: 6]
                align: 'center',
                verticalAlign: 'middle',
                style: { color: '#64748b', fontSize: '14px' }
            }
        };

        // 4. Tự động tinh chỉnh cấu hình (Grid, Stroke) tùy theo họ biểu đồ[cite: 6]
        if (!['pie', 'donut', 'radialBar'].includes(chartType)) {
            // Đối với họ biểu đồ tọa độ (Bar, Line, Area), thêm lưới nền đứt nét[cite: 6]
            this.defaultOptions.grid = { borderColor: '#e0e0e0', strokeDashArray: 4 };

            // Đối với họ biểu đồ đường, làm mềm các góc gập (smooth)[cite: 6]
            if (['line', 'area'].includes(chartType)) {
                this.defaultOptions.stroke = { curve: 'smooth', width: 2 };
            }
        }

        // 5. Trộn (Deep Merge) cấu hình mặc định và cấu hình người dùng truyền vào[cite: 6]
        this.options = this.mergeDeep(this.defaultOptions, customOptions);
    }

    /**
     * Khởi tạo instance của ApexCharts và vẽ lên màn hình[cite: 6]
     */
    render() {
        if (!this.target) {
            console.warn('BaseChart: Không tìm thấy vùng chứa (DOM Element) để vẽ biểu đồ.');
            return null;
        }
        this.chartInstance = new ApexCharts(this.target, this.options);
        return this.chartInstance.render();
    }

    /**
     * Hàm đẩy hoặc làm mới dữ liệu cho biểu đồ. 
     * Thường dùng khi gọi API qua AJAX để lấy dữ liệu mới mà không load lại trang[cite: 6].
     * 
     * @param {Object} data - Dữ liệu truyền vào (chứa labels, categories, series tùy loại chart)
     */
    setData(data) {
        if (!this.chartInstance || !data) return;

        const currentType = this.options.chart.type;

        // Xử lý riêng cho họ biểu đồ Hình tròn (Pie, Donut) - Dùng 'labels'[cite: 6]
        if (['pie', 'donut', 'radialBar'].includes(currentType)) {
            this.chartInstance.updateOptions({
                labels: data.labels || []
            }, false, true); // Các tham số false, true giúp cập nhật có animation mượt mà[cite: 6]
            this.chartInstance.updateSeries(data.series || [], true);
        }
        // Xử lý cho họ biểu đồ Trục tọa độ (Bar, Line) - Dùng 'xaxis.categories'[cite: 6]
        else {
            this.chartInstance.updateOptions({
                xaxis: { categories: data.categories || [] }
            }, false, true);
            this.chartInstance.updateSeries(data.series || [], true);
        }
    }

    /**
     * BỔ SUNG: Cập nhật bất kỳ thông số cấu hình nào của biểu đồ sau khi đã render.
     * (Ví dụ: Đổi màu sắc chart khi đổi theme Sáng/Tối, hoặc ẩn hiện DataLabels).
     * 
     * @param {Object} newOptions - Các cấu hình ApexCharts muốn đè vào
     */
    updateOptions(newOptions) {
        if (this.chartInstance) {
            this.chartInstance.updateOptions(newOptions, false, true);
        }
    }

    /**
     * Hủy biểu đồ và giải phóng bộ nhớ.
     * Bắt buộc gọi hàm này khi bạn điều hướng trang trong các ứng dụng SPA (React, Vue, hoặc Vanilla JS router) 
     * để tránh rò rỉ bộ nhớ (Memory Leak)[cite: 6].
     */
    destroy() {
        if (this.chartInstance) {
            this.chartInstance.destroy();
            this.chartInstance = null;
        }
    }

    /**
     * Tiện ích nội bộ: Trộn 2 Object lồng nhau (Deep Merge)[cite: 6].
     * Giúp hợp nhất các cấu hình nhiều tầng của ApexCharts mà không làm mất thuộc tính cũ[cite: 6].
     */
    mergeDeep(target, source) {
        const output = { ...target };
        if (this.isObject(target) && this.isObject(source)) {
            Object.keys(source).forEach(key => {
                if (this.isObject(source[key])) {
                    if (!(key in target)) Object.assign(output, { [key]: source[key] });
                    else output[key] = this.mergeDeep(target[key], source[key]);
                } else {
                    // Nếu là Mảng (Array) hoặc kiểu dữ liệu gốc (String, Number), ghi đè trực tiếp[cite: 6]
                    Object.assign(output, { [key]: source[key] });
                }
            });
        }
        return output;
    }

    /**
     * Tiện ích nội bộ: Kiểm tra xem biến có phải là Object hợp lệ hay không[cite: 6]
     */
    isObject(item) {
        return item && typeof item === 'object' && !Array.isArray(item);
    }
}