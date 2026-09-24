/* File: public/assets/js/Extrusion/edit_suggestion.js */

let listData = {
    list_bobin: [],
    list_employee: [],
    list_product: [],
    list_material_lot: [],
    list_extrusion_machine: [],
    list_material: [],
    list_rack: [],
    list_day: [],
    list_month: [],
    list_year: [],
};

// 1. TẢI DỮ LIỆU DANH MỤC
document.addEventListener("DOMContentLoaded", () => {
    fetch(API_BASE_URL + "listdata/getListData")
        .then((res) => res.json())
        .then((response) => {
            if (response.success) {
                listData = response;
                reversePrintLotAll();
            } else {
                console.error("❌ Không thể tải danh mục gợi ý.");
            }
        })
        .catch(console.error);
});

// 2. LOGIC GỢI Ý KHI NHẬP LIỆU
document.addEventListener("input", function (e) {
    const target = e.target;
    const container = target.closest('.bobin-item') || document;

    // --- GỢI Ý MÃ SẢN PHẨM ---
    if (target.id === "product_code") {
        const val = target.value.toLowerCase().trim();
        const box = container.querySelector("#product_suggestions");
        const poCodeEl = container.querySelector("#production_order_code");
        if (!box) return;

        // Tự động xóa mã chỉ thị nếu đang sửa mã sản phẩm
        if (poCodeEl) poCodeEl.value = "";

        box.innerHTML = "";
        if (!listData.list_product || !val) return (box.style.display = "none");

        // Khớp chính xác (quét QR / gõ xong)
        const exactMatch = listData.list_product.find(p => p.product_code && p.product_code.toLowerCase() === val);
        if (exactMatch) {
            if (poCodeEl) poCodeEl.value = exactMatch.production_order_code;
            box.style.display = "none";
            return;
        }

        const matches = listData.list_product.filter(product => {
            const po = product.production_order_code || "";
            const pc = product.product_code || "";
            return po.toLowerCase().includes(val) || pc.toLowerCase().includes(val);
        });

        if (matches.length > 0) {
            box.style.display = "block";
            matches.forEach(product => {
                const div = document.createElement("div");
                div.className = "suggestion-item";
                div.textContent = `${product.product_code}`;
                div.onclick = () => {
                    if (poCodeEl) poCodeEl.value = product.production_order_code;
                    target.value = product.product_code;
                    box.style.display = "none";
                };
                box.appendChild(div);
            });
        } else box.style.display = "none";
    }

    // --- GỢI Ý NHÂN VIÊN ---
    if (target.id === "extrusion_employee_code") {
        const val = target.value.toLowerCase().trim();
        const box = container.querySelector("#employee_suggestions");
        const empName = container.querySelector("#extrusion_employee_name");
        if (!box) return;

        // Tự động xóa họ tên nhân viên nếu đang sửa mã
        if (empName) empName.value = "";

        box.innerHTML = "";
        if (!listData.list_employee || !val) return (box.style.display = "none");

        const exactMatch = listData.list_employee.find(emp => emp.employee_code && emp.employee_code.toLowerCase() === val);
        if (exactMatch) {
            if (empName) empName.value = exactMatch.employee_name;
            box.style.display = "none";
            return;
        }

        const matches = listData.list_employee.filter(emp =>
            (emp.employee_code && emp.employee_code.toLowerCase().includes(val)) ||
            (emp.employee_name && emp.employee_name.toLowerCase().includes(val))
        );

        if (matches.length > 0) {
            box.style.display = "block";
            matches.forEach(emp => {
                const div = document.createElement("div");
                div.className = "suggestion-item";
                div.textContent = `${emp.employee_code} - ${emp.employee_name}`;
                div.onclick = () => {
                    target.value = emp.employee_code;
                    if (empName) empName.value = emp.employee_name;
                    box.style.display = "none";
                };
                box.appendChild(div);
            });
        } else box.style.display = "none";
    }

    // --- GỢI Ý VỊ TRÍ RACK ---
    if (target.id === "rack_code") {
        const val = target.value.toLowerCase().trim();
        const box = container.querySelector("#rack_suggestions");
        if (!box) return;

        box.innerHTML = "";
        if (!listData.list_rack || !val) return (box.style.display = "none");

        const matches = listData.list_rack.filter(item => item.rack_code && item.rack_code.toLowerCase().includes(val));
        if (matches.length > 0) {
            box.style.display = "block";
            matches.forEach(item => {
                const div = document.createElement("div");
                div.className = "suggestion-item";
                div.textContent = item.rack_code;
                div.onclick = () => { target.value = item.rack_code; box.style.display = "none"; };
                box.appendChild(div);
            });
        } else box.style.display = "none";
    }

    // --- GỢI Ý LOT VẬT LIỆU ---
    if (target.id === "material_lot") {
        const val = target.value.toLowerCase().trim();
        const box = container.querySelector("#material_lot_suggestions");
        if (!box) return;

        box.innerHTML = "";
        if (!listData.list_material_lot || !val) return (box.style.display = "none");

        const matches = listData.list_material_lot.filter(item => item.lot && item.lot.toLowerCase().includes(val));
        if (matches.length > 0) {
            box.style.display = "block";
            matches.forEach(item => {
                const div = document.createElement("div");
                div.className = "suggestion-item";
                div.textContent = item.lot;
                div.onclick = () => { target.value = item.lot; box.style.display = "none"; };
                box.appendChild(div);
            });
        } else box.style.display = "none";
    }

    // --- GỢI Ý VẬT LIỆU ---
    if (target.id === "material") {
        const val = target.value.toLowerCase().trim();
        const box = container.querySelector("#material_suggestions");
        if (!box) return;

        box.innerHTML = "";
        if (!listData.list_material || !val) return (box.style.display = "none");

        const uniqueBrands = [...new Set(listData.list_material.map(item => item.brand))]
            .filter(brand => brand && brand.toLowerCase().includes(val));

        if (uniqueBrands.length > 0) {
            box.style.display = "block";
            uniqueBrands.forEach(value => {
                const div = document.createElement("div");
                div.className = "suggestion-item";
                div.textContent = value;
                div.onclick = () => { target.value = value; box.style.display = "none"; updatePrintLot(container); };
                box.appendChild(div);
            });
        } else box.style.display = "none";
    }

    // --- GỢI Ý MÁY ĐÙN ---
    if (target.id === "extrusion_machine") {
        const val = target.value.toLowerCase().trim();
        const box = container.querySelector("#machine_suggestions");
        if (!box) return;

        box.innerHTML = "";
        if (!listData.list_extrusion_machine || !val) return (box.style.display = "none");

        const matches = listData.list_extrusion_machine.filter(item => item.machine_name && item.machine_name.toLowerCase().includes(val));
        if (matches.length > 0) {
            box.style.display = "block";
            matches.forEach(item => {
                const div = document.createElement("div");
                div.className = "suggestion-item";
                div.textContent = item.machine_name;
                div.onclick = () => { target.value = item.machine_name; box.style.display = "none"; updatePrintLot(container); };
                box.appendChild(div);
            });
        } else box.style.display = "none";
    }

    // --- THEO DÕI THAY ĐỔI ĐỂ TÍNH LẠI LOT IN ---
    if (["extrusion_machine", "material", "grinding_time", "extrusion_date", "finish_time"].includes(target.id)) {
        clearTimeout(container.printLotTimeout);
        container.printLotTimeout = setTimeout(() => updatePrintLot(container), 300);
    }
});

// Click ra ngoài đóng box gợi ý
document.addEventListener("click", (e) => {
    if (!e.target.closest('.suggestion-wrapper')) {
        document.querySelectorAll('.suggestion-box').forEach(b => b.style.display = 'none');
    }
});

// 3. TỰ ĐỘNG TÍNH TOÁN VÀ DỊCH NGƯỢC PRINT LOT
function findCode(list, keyToCompare, valueToCompare, keyToReturn) {
    if (!list || !Array.isArray(list)) return "";
    const found = list.find(item => String(item[keyToCompare]).trim().toLowerCase() === String(valueToCompare).trim().toLowerCase());
    return found ? found[keyToReturn] : "";
}

function updatePrintLot(container) {
    const machineEl = container.querySelector("#extrusion_machine");
    const materialEl = container.querySelector("#material");
    const grindingEl = container.querySelector("#grinding_time");
    const dateInput = container.querySelector("#extrusion_date") || container.querySelector("#finish_time");
    const printLotEl = container.querySelector("#print_lot");

    if (!machineEl || !materialEl || !grindingEl || !dateInput || !printLotEl) return;

    const machineName = machineEl.value.trim();
    const materialBrand = materialEl.value.trim();
    const grindingTime = grindingEl.value.trim();
    const dateValue = dateInput.value.trim();

    if (!machineName || !materialBrand || grindingTime === "" || !dateValue) return;

    let day, month, year;
    if (dateValue.includes("-")) {
        const parts = dateValue.split("-");
        year = parseInt(parts[0], 10);
        month = parseInt(parts[1], 10);
        day = parseInt(parts[2], 10);
    } else if (dateValue.includes("/")) {
        const parts = dateValue.split(" ")[0].split("/");
        day = parseInt(parts[0], 10);
        month = parseInt(parts[1], 10);
        year = parseInt(parts[2], 10);
    }

    if (!day || !month || !year) return;

    const machineCode = findCode(listData.list_extrusion_machine, "machine_name", machineName, "machine_code");
    const materialCode = listData.list_material
        .filter(item => String(item.brand).trim().toLowerCase() === String(materialBrand).trim().toLowerCase() && item.grinding_time == grindingTime)
        .map(item => item.code)[0] || "";

    const dayCode = findCode(listData.list_day, "day", day, "code");
    const monthCode = findCode(listData.list_month, "month", month, "code");
    const yearCode = findCode(listData.list_year, "year", year, "code");

    if (machineCode && materialCode && dayCode && monthCode && yearCode) {
        printLotEl.value = `${machineCode}${materialCode}${yearCode}${monthCode}${dayCode}`;
    }
}

function reversePrintLotAll() {
    document.querySelectorAll('.bobin-item').forEach(container => reversePrintLot(container));
}

function reversePrintLot(container) {
    const printLotEl = container.querySelector("#print_lot");
    if (!printLotEl) return;

    const lotString = printLotEl.value.trim();
    if (!lotString) return;

    let remainingStr = lotString;
    const result = { machine: "", material: "", grinding_time: "", day: "", month: "", year: "" };

    const dayObj = listData.list_day.find(item => remainingStr.endsWith(item.code));
    if (dayObj) { result.day = dayObj.day; remainingStr = remainingStr.slice(0, -dayObj.code.length); }

    const monthObj = listData.list_month.find(item => remainingStr.endsWith(item.code));
    if (monthObj) { result.month = monthObj.month; remainingStr = remainingStr.slice(0, -monthObj.code.length); }

    const yearObj = listData.list_year.find(item => remainingStr.endsWith(item.code));
    if (yearObj) { result.year = yearObj.year; remainingStr = remainingStr.slice(0, -yearObj.code.length); }

    const machineObj = listData.list_extrusion_machine.find(item => remainingStr.startsWith(item.machine_code));
    if (machineObj) { result.machine = machineObj.machine_name; remainingStr = remainingStr.slice(machineObj.machine_code.length); }

    const materialObj = listData.list_material.find(item => item.code === remainingStr);
    if (materialObj) { result.material = materialObj.brand; result.grinding_time = materialObj.grinding_time; }

    if (result.machine) { const el = container.querySelector("#extrusion_machine"); if (el && !el.value) el.value = result.machine; }
    if (result.material) { const el = container.querySelector("#material"); if (el && !el.value) el.value = result.material; }
    if (result.grinding_time !== undefined) { const el = container.querySelector("#grinding_time"); if (el && el.value === "") el.value = result.grinding_time; }

    if (result.day && result.month && result.year) {
        const formattedDay = String(result.day).padStart(2, '0');
        const formattedMonth = String(result.month).padStart(2, '0');
        const dateInput = container.querySelector("#extrusion_date");
        if (dateInput) {
            dateInput.value = `${result.year}-${formattedMonth}-${formattedDay}`;
        }
    }
}