/* File: public/assets/js/suggestions.js */

//TODO 1. BIẾN TOÀN CỤC
let listData = {
    list_bobin: [],
    list_employee: [],
    list_product: [],
    list_material_lot: [],
    list_extrusion_machine: [],
    list_material: [],
    list_day: [],
    list_month: [],
    list_year: [],
};

//TODO 2. HÀM CLOCK (Áp dụng cho tất cả các thẻ có id="finish_time" trong danh sách)
setInterval(() => {
    const d = new Date();
    const t = d.toLocaleTimeString("vi-VN", { hour12: false });
    const day = d.getDate().toString().padStart(2, "0");
    const month = (d.getMonth() + 1).toString().padStart(2, "0");
    const year = d.getFullYear();
    const dateStr = `${day}/${month}/${year}`;

    // Update tất cả các ô có id="finish_time"
    document.querySelectorAll("#finish_time").forEach(el => {
        el.value = dateStr + " " + t;
    });
}, 1000);

//TODO 3. LOAD DATA
document.addEventListener("DOMContentLoaded", () => {
    console.log("🚀 Bắt đầu gọi API (Suggestions)...");
    fetch(API_BASE_URL + "listdata/getListData")
        .then((res) => res.json())
        .then((response) => {
            if (response.success) {
                console.log("📥 Dữ liệu nhận về:", response);
                listData = response;
                reversePrintLotAll(); // Tự động điền ngược thông tin cho toàn bộ list nếu có mã Print Lot
                console.log(`👂 Bắt đầu theo dõi nhập dữ liệu`);
            } else {
                console.error("❌ Lỗi từ API: Truy xuất dữ liệu ListData không thành công.");
            }
        })
        .catch(console.error);
});

//TODO 4. Hàm hỗ trợ điền Datalist
function fillDatalist(key, dataArray) {
    const el = document.getElementById(key);

    if (!el) return;
    if (!Array.isArray(dataArray) || dataArray.length === 0) {
        console.warn(`⚠️ Dữ liệu cho ${key} không hợp lệ hoặc rỗng.`);
        return;
    }

    const fieldMapping = {
        list_bobin: "bobin_identification_code",
        list_employee: "employee_code",
        list_product: "product_code",
        list_material_lot: "lot",
        list_extrusion_machine: "machine_name",
        list_material: "brand",
    };

    const keyName = fieldMapping[key];

    const optionsHTML = dataArray.map((item) => {
        if (key === "list_employee") {
            const code = item["employee_code"];
            const name = item["employee_name"];
            if (code) return `<option value="${code} - ${name}">`;
        }
        if (key === "list_product") {
            const production_order_code = item["production_order_code"];
            const product_code = item["product_code"];
            if (product_code) return `<option value="${production_order_code} - ${product_code}">`;
        }
        if (keyName && item[keyName]) {
            return `<option value="${item[keyName]}">`;
        }
        return "";
    }).join("");

    el.innerHTML = optionsHTML;
}

//TODO 5. LOGIC GỢI Ý (Sử dụng Event Delegation để xử lý list dùng ID)
document.addEventListener("input", function (e) {
    const target = e.target;
    // Xác định container cha (chính là 1 dòng bobin-item) để không bị nhầm lẫn giữa các dòng
    const container = target.closest('.bobin-item') || document;

    // --- XỬ LÝ GỢI Ý MÃ BOBIN ---
    if (target.id === "bobin_identification_code") {
        const val = target.value.toLowerCase().trim();
        const bobinBox = container.querySelector("#bobin_suggestions");
        const bobinSize = container.querySelector("#bobin_size");
        if (!bobinBox) return;

        bobinBox.innerHTML = "";
        if (!listData.list_bobin || listData.list_bobin.length === 0 || !val) {
            bobinBox.style.display = "none";
            return;
        }

        const matches = listData.list_bobin.filter((item) => {
            const code = item.bobin_identification_code || "";
            // Bỏ điều kiện status === "Ready" nếu bạn dùng chung cho cả form list
            return code.toLowerCase().includes(val);
        });

        if (matches.length > 0) {
            bobinBox.style.display = "block";
            matches.forEach((item) => {
                const code = item.bobin_identification_code;
                const size = item.bobin_size || '';

                const div = document.createElement("div");
                div.className = "suggestion-item";
                div.textContent = size ? `${code} - ${size}` : code;

                div.onclick = () => {
                    target.value = code;
                    if (bobinSize) bobinSize.value = size;
                    bobinBox.style.display = "none";
                    if (typeof checkBobinStatus === "function") checkBobinStatus(code);
                };
                bobinBox.appendChild(div);
            });
        } else {
            bobinBox.style.display = "none";
        }
    }

    // --- XỬ LÝ GỢI Ý MATERIAL LOT ---
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

    // --- XỬ LÝ GỢI Ý MATERIAL ---
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

    // --- XỬ LÝ GỢI Ý MACHINE ---
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

    // --- XỬ LÝ GỢI Ý PRINT LOT ---
    if (target.id === "print_lot") {
        const val = target.value.toLowerCase().trim();
        const box = container.querySelector("#print_lot_suggestions");
        if (!box) return;

        box.innerHTML = "";
        if (!listData.list_print_lot || !val) return (box.style.display = "none");

        if (listData.list_print_lot.length > 0) {
            box.style.display = "block";
            listData.list_print_lot.forEach(item => {
                const div = document.createElement("div");
                div.className = "suggestion-item";
                div.textContent = item.lot;
                div.onclick = () => { target.value = item.lot; box.style.display = "none"; reversePrintLot(container); };
                box.appendChild(div);
            });
        } else box.style.display = "none";
    }

    // --- XỬ LÝ GỢI Ý NHÂN VIÊN ---
    if (target.id === "extrusion_employee_code") {
        const val = target.value.toLowerCase().trim();
        const box = container.querySelector("#employee_suggestions");
        const empName = container.querySelector("#extrusion_employee_name");
        if (!box) return;

        box.innerHTML = "";
        if (!listData.list_employee || !val) return (box.style.display = "none");

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

    // --- XỬ LÝ GỢI Ý SẢN PHẨM ---
    if (target.id === "product_code") {
        const val = target.value.toLowerCase().trim();
        const box = container.querySelector("#product_suggestions");
        const poCodeEl = container.querySelector("#production_order_code");
        if (!box) return;

        box.innerHTML = "";
        if (!listData.list_product || !val) return (box.style.display = "none");

        const matches = listData.list_product.filter(product => {
            const poCode = product.production_order_code || "";
            const pCode = product.product_code || "";
            return poCode.toLowerCase().includes(val) || pCode.toLowerCase().includes(val);
        });

        if (matches.length > 0) {
            box.style.display = "block";
            matches.forEach(product => {
                const div = document.createElement("div");
                div.className = "suggestion-item";
                div.textContent = `${product.production_order_code} - ${product.product_code}`;
                div.onclick = () => {
                    if (poCodeEl) poCodeEl.value = product.production_order_code;
                    target.value = product.product_code;
                    box.style.display = "none";
                };
                box.appendChild(div);
            });
        } else box.style.display = "none";
    }
});


//TODO 6. LOGIC TỰ ĐỘNG TẠO MÃ LOT IN & REVERSE LOT (Đã điều chỉnh theo Container)

// Lắng nghe sự thay đổi của các trường tạo Print Lot trên toàn trang
document.addEventListener("input", function (e) {
    if (["extrusion_machine", "material", "grinding_time", "finish_time"].includes(e.target.id)) {
        const container = e.target.closest('.bobin-item') || document;

        // Debounce updatePrintLot theo từng container riêng biệt
        clearTimeout(container.printLotTimeout);
        container.printLotTimeout = setTimeout(() => updatePrintLot(container), 500);
    }
});

function findCode(list, keyToCompare, valueToCompare, keyToReturn) {
    if (!list || !Array.isArray(list)) return "";
    const found = list.find(item => String(item[keyToCompare]).trim().toLowerCase() === String(valueToCompare).trim().toLowerCase());
    return found ? found[keyToReturn] : "";
}

function updatePrintLot(container) {
    const machineEl = container.querySelector("#extrusion_machine");
    const materialEl = container.querySelector("#material");
    const grindingEl = container.querySelector("#grinding_time");
    const dateInput = container.querySelector("#finish_time");
    const printLotEl = container.querySelector("#print_lot");

    if (!machineEl || !materialEl || !grindingEl || !dateInput || !printLotEl) return;

    const machineName = machineEl.value.trim();
    const materialBrand = materialEl.value.trim();
    const grindingTime = grindingEl.value.trim();
    const dateValue = dateInput.value.trim();

    if (!machineName || !materialBrand || grindingTime === "" || !dateValue) {
        if (printLotEl.value !== "") printLotEl.value = "";
        return;
    }

    const dateObj = new Date(dateValue.split(" ")[0].split("/").reverse().join("-"));
    const day = dateObj.getDate();
    const month = dateObj.getMonth() + 1;
    const year = dateObj.getFullYear();

    const machineCode = findCode(listData.list_extrusion_machine, "machine_name", machineName, "machine_code");

    const materialCode = listData.list_material
        .filter(item => String(item.brand).trim().toLowerCase() === String(materialBrand).trim().toLowerCase() && item.grinding_time == grindingTime)
        .map(item => item.code)[0] || "";

    const dayCode = findCode(listData.list_day, "day", day, "code");
    const monthCode = findCode(listData.list_month, "month", month, "code");
    const yearCode = findCode(listData.list_year, "year", year, "code");

    if (!machineCode || !materialCode || !dayCode || !monthCode || !yearCode) {
        if (printLotEl.value !== "") printLotEl.value = "";
        return;
    }

    const lotString = `${machineCode}${materialCode}${yearCode}${monthCode}${dayCode}`;
    if (printLotEl.value !== lotString) {
        printLotEl.value = lotString;
    }
}

// Chạy reversePrintLot cho toàn bộ list khi mới load trang xong
function reversePrintLotAll() {
    const items = document.querySelectorAll('.bobin-item');
    if (items.length > 0) {
        items.forEach(container => reversePrintLot(container));
    } else {
        reversePrintLot(document); // Chạy cho form đơn lẻ
    }
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

    if (result.machine) { const el = container.querySelector("#extrusion_machine"); if (el) el.value = result.machine; }
    if (result.material) { const el = container.querySelector("#material"); if (el) el.value = result.material; }
    if (result.grinding_time !== undefined) { const el = container.querySelector("#grinding_time"); if (el) el.value = result.grinding_time; }

    if (result.day && result.month && result.year) {
        const formattedDay = String(result.day).padStart(2, '0');
        const formattedMonth = String(result.month).padStart(2, '0');
        const dateString = `${formattedDay}/${formattedMonth}/${result.year}`;

        const dateInput = container.querySelector('#finish_time') || container.querySelector("#extrusion_date");
        if (dateInput) dateInput.value = dateString;
    }
}