/**
 * i18n.js - Hệ thống đa ngôn ngữ hoàn toàn Local / Offline (Single Source of Truth)
 * Hỗ trợ: Tiếng Việt (vi), English (en), 日本語 (ja)
 * Nguồn từ điển gốc duy nhất: config/default_translations.json + config/custom_translations.json
 * Tự động dịch triệt để toàn bộ nội dung DOM, bảng biểu, form, nút bấm, placeholder.
 */
(function (window, document) {
  "use strict";

  // 1. KHỞI TẠO TỪ ĐIỂN ĐA NGÔN NGỮ TỪ NGUỒN DUY NHẤT (SINGLE SOURCE OF TRUTH)
  const BASE_META = {
    vi: { lang_name: "Tiếng Việt", flag: "🇻🇳", short: "VI" },
    en: { lang_name: "English", flag: "🇬🇧", short: "EN" },
    ja: { lang_name: "日本語", flag: "🇯🇵", short: "JA" },
  };

  const DEFAULT_DICT = {
    vi: Object.assign({}, BASE_META.vi),
    en: Object.assign({}, BASE_META.en),
    ja: Object.assign({}, BASE_META.ja),
  };

  const CUSTOM_DICT = {
    vi: {},
    en: {},
    ja: {},
  };

  const DICT = {
    vi: Object.assign({}, BASE_META.vi),
    en: Object.assign({}, BASE_META.en),
    ja: Object.assign({}, BASE_META.ja),
  };

  function rebuildMergedDict() {
    ["vi", "en", "ja"].forEach((lang) => {
      DICT[lang] = Object.assign(
        {},
        BASE_META[lang],
        DEFAULT_DICT[lang] || {},
        CUSTOM_DICT[lang] || {},
      );
      const c = CUSTOM_DICT[lang] || {};
      if (c["btn_cancel"] && !c["cancel"]) {
        DICT[lang]["cancel"] = c["btn_cancel"];
      }
      if (c["btn_confirm"] && !c["confirm"]) {
        DICT[lang]["confirm"] = c["btn_confirm"];
      }
      if (c["cancel"] && !c["btn_cancel"]) {
        DICT[lang]["btn_cancel"] = c["cancel"];
      }
      if (c["confirm"] && !c["btn_confirm"]) {
        DICT[lang]["btn_confirm"] = c["confirm"];
      }
    });
  }

  function applyDictionaries(defaultObj, customObj, hashStr) {
    if (defaultObj && typeof defaultObj === "object") {
      ["vi", "en", "ja"].forEach((lang) => {
        if (defaultObj[lang] && typeof defaultObj[lang] === "object") {
          DEFAULT_DICT[lang] = Object.assign(
            {},
            BASE_META[lang],
            defaultObj[lang],
          );
        }
      });
      window.__DEFAULT_I18N__ = DEFAULT_DICT;
      try {
        localStorage.setItem(
          "webbobin_default_i18n",
          JSON.stringify(DEFAULT_DICT),
        );
      } catch (e) { }
    }

    if (customObj && typeof customObj === "object") {
      ["vi", "en", "ja"].forEach((lang) => {
        if (customObj[lang] && typeof customObj[lang] === "object") {
          CUSTOM_DICT[lang] = Object.assign({}, customObj[lang]);
        }
      });
      window.__CUSTOM_I18N__ = CUSTOM_DICT;
      try {
        localStorage.setItem(
          "webbobin_custom_i18n",
          JSON.stringify(CUSTOM_DICT),
        );
      } catch (e) { }
    }

    if (hashStr && typeof hashStr === "string") {
      window.__I18N_HASH__ = hashStr;
      try {
        localStorage.setItem("webbobin_i18n_hash", hashStr);
      } catch (e) { }
    }

    rebuildMergedDict();
  }

  // 1.1 Khởi tạo đồng bộ ngay từ localStorage cache nếu có (tránh độ trễ khi tải trang)
  try {
    const cachedDefault = localStorage.getItem("webbobin_default_i18n");
    if (cachedDefault) {
      const parsedDef = JSON.parse(cachedDefault);
      if (parsedDef && typeof parsedDef === "object") {
        ["vi", "en", "ja"].forEach((lang) => {
          if (parsedDef[lang] && typeof parsedDef[lang] === "object") {
            DEFAULT_DICT[lang] = Object.assign(
              {},
              BASE_META[lang],
              parsedDef[lang],
            );
          }
        });
      }
    }
    const cachedCustom = localStorage.getItem("webbobin_custom_i18n");
    if (cachedCustom) {
      const parsedCus = JSON.parse(cachedCustom);
      if (parsedCus && typeof parsedCus === "object") {
        ["vi", "en", "ja"].forEach((lang) => {
          if (parsedCus[lang] && typeof parsedCus[lang] === "object") {
            CUSTOM_DICT[lang] = Object.assign({}, parsedCus[lang]);
          }
        });
      }
    }
    rebuildMergedDict();
  } catch (e) { }

  // 1.2 Gộp trực tiếp từ biến toàn cục window.__DEFAULT_I18N__ và window.__CUSTOM_I18N__ do PHP Backend nhúng
  if (typeof window !== "undefined") {
    applyDictionaries(
      window.__DEFAULT_I18N__ || null,
      window.__CUSTOM_I18N__ || null,
      window.__I18N_HASH__ || null,
    );
  }

  // 1.3 Cung cấp API toàn cục để cập nhật từ điển từ bất kỳ component nào
  window.loadAndMergeCustomTranslations = function (
    customObj,
    defaultObj,
    hashStr,
  ) {
    applyDictionaries(
      defaultObj || window.__DEFAULT_I18N__ || null,
      customObj || window.__CUSTOM_I18N__ || null,
      hashStr || window.__I18N_HASH__ || null,
    );
    if (
      document.readyState === "interactive" ||
      document.readyState === "complete"
    ) {
      deepTranslateDOM();
    }
  };

  window.getAppDictionary = function () {
    return DICT;
  };

  // 1.4 Dự phòng: Tự động đồng bộ từ điển từ máy chủ nếu chưa có dữ liệu khởi tạo
  if (
    typeof fetch === "function" &&
    Object.keys(DEFAULT_DICT.vi || {}).length <= 3
  ) {
    fetch("/WEB_BOBIN/public/index.php?url=auth/getTranslations", {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then((res) => res.json())
      .then((data) => {
        if (data && data.success) {
          window.loadAndMergeCustomTranslations(
            data.custom_dict || {},
            data.default_dict || {},
            data.hash || "",
          );
        }
      })
      .catch(() => { });
  }

  // 2. TỪ ĐIỂN CỤ THỂ DÀNH CHO ÁNH XẠ NỘI DUNG DOM (TABLE HEADERS, FORM LABELS, BUTTONS)
  const DOM_MAPPINGS = {
    // Table Headers (th)
    headers: [
      { vi: "STT", en: "No.", ja: "No." },
      { vi: "Mã Bobin", en: "Bobin Code", ja: "ボビン番号" },
      { vi: "Mã chỉ thị SX", en: "PO Code", ja: "製造指示書" },
      { vi: "Mã chỉ thị (PO)", en: "PO Code", ja: "製造指示(PO)" },
      { vi: "Mã PO", en: "PO Code", ja: "PO番号" },
      { vi: "Mã sản phẩm", en: "Product Code", ja: "製品コード" },
      { vi: "Mã SP", en: "Product Code", ja: "製品コード" },
      { vi: "Chiều dài (m)", en: "Length (m)", ja: "長さ (m)" },
      { vi: "Chiều dài", en: "Length", ja: "長さ" },
      { vi: "Khối lượng (kg)", en: "Weight (kg)", ja: "重量 (kg)" },
      { vi: "Khối lượng", en: "Weight", ja: "重量" },
      { vi: "Lô in", en: "Print Lot", ja: "印字ロット" },
      { vi: "Máy đùn", en: "Extruder", ja: "押出機" },
      { vi: "Người đùn", en: "Extrusion Operator", ja: "押出作業者" },
      { vi: "Thời gian tạo", en: "Created Time", ja: "登録日時" },
      { vi: "Ngày tạo", en: "Created Date", ja: "登録日" },
      { vi: "QC Ngoại quan", en: "QC Visual", ja: "QC外観" },
      { vi: "Lỗi QC", en: "QC Defect", ja: "QC不良" },
      { vi: "Máy cuộn", en: "Winder", ja: "巻取機" },
      { vi: "Người cuộn", en: "Winding Operator", ja: "巻取作業者" },
      { vi: "Thời gian cuộn", en: "Winding Time", ja: "巻取日時" },
      { vi: "Trạng thái", en: "Status", ja: "ステータス" },
      { vi: "Thao tác", en: "Actions", ja: "操作" },
      { vi: "Công đoạn báo hủy", en: "Scrap Stage", ja: "廃棄工程" },
      { vi: "Lý do hủy", en: "Scrap Reason", ja: "廃棄理由" },
      { vi: "Người báo hủy", en: "Reported By", ja: "廃棄申請者" },
      { vi: "Thời gian báo", en: "Reported Time", ja: "申請日時" },
      { vi: "Họ và tên", en: "Full Name", ja: "氏名" },
      { vi: "Mã nhân viên", en: "Employee ID", ja: "社員番号" },
      { vi: "Tên đăng nhập", en: "Username", ja: "ログインID" },
      { vi: "Tài khoản", en: "Username", ja: "ログインID" },
      { vi: "Vai trò / Nhóm", en: "Role / Group", ja: "役職/グループ" },
      { vi: "Vai trò", en: "Role", ja: "役職" },
      { vi: "Mật khẩu", en: "Password", ja: "パスワード" },
      { vi: "Tình trạng làm việc", en: "Work Status", ja: "勤務状態" },
      { vi: "Tình trạng", en: "Status", ja: "状態" },
      { vi: "Lần đầu đăng nhập", en: "First Login", ja: "初回ログイン" },
      { vi: "Đổi MK lần đầu", en: "First Login PW", ja: "初回PW変更" },
      {
        vi: "Mã NV / Tài khoản",
        en: "Employee ID / Username",
        ja: "社員番号 / ログインID",
      },
      { vi: "Họ tên", en: "Full Name", ja: "氏名" },
      { vi: "Mã bộ phận", en: "Cost Center", ja: "部門コード" },
    ],

    // Form Labels
    labels: [
      { vi: "Mã bộ phận:", en: "Cost Center:", ja: "部門コード:" },
      { vi: "Mã bộ phận", en: "Cost Center", ja: "部門コード" },
      { vi: "Mã Bobin", en: "Bobin Code", ja: "ボビン番号" },
      { vi: "Mã sản phẩm", en: "Product Code", ja: "製品コード" },
      {
        vi: "Mã chỉ thị SX (PO)",
        en: "Production Order (PO)",
        ja: "製造指示書 (PO)",
      },
      { vi: "Chiều dài (m)", en: "Length (m)", ja: "長さ (m)" },
      { vi: "Khối lượng (kg)", en: "Weight (kg)", ja: "重量 (kg)" },
      { vi: "Máy đùn", en: "Extruder Machine", ja: "押出機" },
      { vi: "Máy cuộn", en: "Winder Machine", ja: "巻取機" },
      { vi: "Nguyên liệu", en: "Material", ja: "原材料" },
      { vi: "Lô nguyên liệu", en: "Material Lot", ja: "材料ロット" },
      { vi: "Tên người thao tác", en: "Operator Name", ja: "作業者氏名" },
      { vi: "Người thao tác", en: "Operator", ja: "作業者" },
      { vi: "Người thực hiện", en: "Operator", ja: "作業者" },
      { vi: "Ca làm việc", en: "Working Shift", ja: "シフト" },
      { vi: "Lô in", en: "Print Lot", ja: "印字ロット" },
      { vi: "Ghi chú", en: "Remarks", ja: "備考" },
      { vi: "Ngoại quan", en: "Appearance", ja: "外観" },
      { vi: "Dạng khuyết tật", en: "Defect Type", ja: "不良種別" },
      { vi: "Nguyên nhân khuyết tật", en: "Defect Cause", ja: "不良原因" },
      { vi: "Từ ngày", en: "From Date", ja: "開始日" },
      { vi: "Đến ngày", en: "To Date", ja: "終了日" },
      { vi: "Họ và tên", en: "Full Name", ja: "氏名" },
      { vi: "Mã số nhân viên", en: "Employee ID", ja: "社員番号" },
      { vi: "Tên đăng nhập", en: "Username", ja: "ログインID" },
      { vi: "Vai trò / Phân quyền", en: "Role / Permission", ja: "役職/権限" },
      { vi: "Mật khẩu", en: "Password", ja: "パスワード" },
      { vi: "Mật khẩu bảo mật", en: "Security Password", ja: "パスワード" },
      { vi: "Tình trạng làm việc", en: "Work Status", ja: "勤務状態" },
      { vi: "Loại Bobin", en: "Bobin Type", ja: "ボビン種別" },
      { vi: "Mã định danh Bobin", en: "Bobin ID Code", ja: "ボビン識別番号" },
      { vi: "Kích thước Bobin", en: "Bobin Size", ja: "ボビンサイズ" },
      { vi: "Số máy đùn", en: "Extruder Machine", ja: "押出機番号" },
      { vi: "Vật liệu", en: "Material", ja: "原材料" },
      { vi: "Số lần nghiền", en: "Grinding Times", ja: "粉砕回数" },
      { vi: "Lot vật liệu", en: "Material Lot", ja: "材料ロット" },
      { vi: "Lot in (Print Lot)", en: "Print Lot", ja: "印字ロット" },
      { vi: "Vị trí Rack", en: "Rack Location", ja: "ラック位置" },
      { vi: "Ngày đùn", en: "Extrusion Date", ja: "押出日" },
      { vi: "Thời gian hoàn thành", en: "Completion Time", ja: "完了日時" },
      { vi: "Ca sản xuất", en: "Production Shift", ja: "製造シフト" },
      { vi: "Đường kính", en: "Diameter", ja: "外径" },
      { vi: "Gel", en: "Gel", ja: "ゲル" },
      { vi: "Dị vật", en: "Foreign Object", ja: "異物" },
      { vi: "Màu sắc", en: "Color", ja: "色調" },
      { vi: "Chữ in", en: "Printing", ja: "印字" },
      {
        vi: "Mã số nhân viên (Tài khoản):",
        en: "Employee ID (Username):",
        ja: "社員番号（ログインID）：",
      },
      {
        vi: "Nhân viên cần cấp lại:",
        en: "Employee to reset:",
        ja: "再発行対象：",
      },
      { vi: "Mã NV Đùn", en: "Extrusion Emp ID", ja: "押出社員番号" },
      { vi: "Họ tên NV Đùn", en: "Extrusion Emp Name", ja: "押出作業者氏名" },
      { vi: "Mã nhân viên đùn", en: "Extrusion Emp ID", ja: "押出社員番号" },
      { vi: "Họ tên nhân viên đùn", en: "Extrusion Emp Name", ja: "押出作業者氏名" },
      { vi: "Mã NV QC", en: "QC Inspector ID", ja: "QC検査員番号" },
      { vi: "Họ tên NV QC", en: "QC Inspector Name", ja: "QC検査員氏名" },
      { vi: "Mã NV Cuộn", en: "Winding Emp ID", ja: "巻取社員番号" },
      { vi: "Họ tên NV Cuộn", en: "Winding Emp Name", ja: "巻取作業者氏名" },
      { vi: "Mã NV", en: "Emp ID", ja: "社員番号" },
      { vi: "Họ tên", en: "Full Name", ja: "氏名" },
      { vi: "Họ tên nhân viên", en: "Employee Name", ja: "作業者氏名" },
      { vi: "Mã nhân viên", en: "Employee ID", ja: "社員番号" },
      { vi: "Thời gian", en: "Time", ja: "日時" },
      { vi: "Thông khí", en: "Airflow", ja: "通気" },
      { vi: "Vị trí RACK", en: "RACK Location", ja: "ラック位置" },
      { vi: "Kích thước", en: "Size", ja: "サイズ" },
      { vi: "Thời điểm hoàn thành", en: "Completion Time", ja: "完了日時" },
      { vi: "Thời điểm hoàn thành cuộn", en: "Winding Completion Time", ja: "巻取完了日時" },
      { vi: "Thời điểm kiểm tra", en: "Inspection Time", ja: "検査日時" },
      { vi: "Mã chỉ thị sản xuất", en: "Production Order (PO)", ja: "製造指示書 (PO)" },
      {
        vi: "Ghi chú QC (Bắt buộc khi đổi loại hoặc báo hủy)",
        en: "QC Note (Required when changing type or cancelling)",
        ja: "QC備考（種別変更・廃棄申請時は必須）",
      },
    ],

    // Section & Card Headers
    sections: [
      {
        vi: "Định danh Bobin & Người phụ trách",
        en: "Bobin Identification & In-charge",
        ja: "ボビン識別・担当者",
      },
      {
        vi: "Quét mã QR hoặc nhập mã Bobin và thông tin nhân viên phụ trách",
        en: "Scan QR or enter Bobin ID and operator info",
        ja: "QRスキャンまたはボビン番号と担当者情報を入力",
      },
      {
        vi: "THÔNG TIN SẢN XUẤT",
        en: "PRODUCTION INFORMATION",
        ja: "製造情報",
      },
      {
        vi: "Mã sản phẩm, Chiều dài, Lot in",
        en: "Product Code, Length, Print Lot",
        ja: "製品コード・長さ・印字ロット",
      },
      {
        vi: "Thời điểm đùn và vị trí Rack",
        en: "Extrusion Time & Rack Location",
        ja: "押出日時・ラック位置",
      },
      {
        vi: "Thời điểm hoàn thành Bobin và vị trí đặt Rack",
        en: "Bobin completion time and Rack storage location",
        ja: "ボビン完了日時とラック保管位置",
      },
      {
        vi: "Nhóm đùn Check lần 1",
        en: "Extrusion 1st Inspection",
        ja: "押出工程1次検査",
      },
      {
        vi: "Xác nhận chất lượng ban đầu trước công đoạn QC",
        en: "Initial quality verification before QC inspection",
        ja: "QC検査前の初期品質確認",
      },
      {
        vi: "Thống kê trạng thái Bobin",
        en: "Bobin Status Statistics",
        ja: "ボビン状態別統計",
      },
      {
        vi: "Thống kê lịch sử cập nhật trạng thái Bobin",
        en: "Bobin Status Update History",
        ja: "ボビン更新履歴統計",
      },
    ],

    // Button texts & links
    buttons: [
      { vi: "Lưu Bobin", en: "Save Bobin", ja: "ボビン保存" },
      { vi: "Lọc", en: "Filter", ja: "絞り込み" },
      { vi: "Bộ lọc", en: "Filter", ja: "絞り込み" },
      { vi: "Xóa lọc", en: "Clear Filter", ja: "リセット" },
      { vi: "Đặt lại bộ lọc", en: "Reset Filter", ja: "フィルタをリセット" },
      { vi: "Xuất file Excel", en: "Export Excel", ja: "Excel出力" },
      { vi: "Xuất Excel", en: "Export Excel", ja: "Excel出力" },
      { vi: "Nhập file Excel", en: "Import Excel", ja: "Excel取込" },
      { vi: "Nhập Excel", en: "Import Excel", ja: "Excel取込" },
      { vi: "Tải file mẫu", en: "Download Template", ja: "ひな形DL" },
      { vi: "Thêm nhân viên mới", en: "Add Employee", ja: "従業員追加" },
      { vi: "Thêm nhân viên", en: "Add Employee", ja: "従業員追加" },
      { vi: "Chỉnh sửa", en: "Edit", ja: "編集" },
      { vi: "Sửa", en: "Edit", ja: "編集" },
      { vi: "Xóa", en: "Delete", ja: "削除" },
      {
        key: "btn_cancel_bobin",
        vi: "Hủy Bobin",
        en: "Cancel Bobin",
        ja: "ボビン廃棄",
        alias: ["Báo hủy Bobin", "🗑️ Hủy Bobin", "🗑️ Báo hủy Bobin"],
      },
      {
        key: "btn_cancel",
        vi: "Hủy",
        en: "Cancel",
        ja: "キャンセル",
        alias: ["Hủy bỏ", "✕ Hủy", "✕ Hủy sửa", "Hủy sửa"],
      },
      {
        key: "btn_cancel",
        vi: "Quay lại",
        en: "Back",
        ja: "戻る",
        alias: ["Trở lại"],
      },
      { key: "close", vi: "Đóng", en: "Close", ja: "閉じる" },
      {
        key: "btn_confirm",
        vi: "Xác nhận",
        en: "Confirm",
        ja: "確認",
        alias: ["Xác nhận lưu", "Xác nhận đổi loại", "Xác nhận gửi"],
      },
      { vi: "Đăng xuất", en: "Logout", ja: "ログアウト" },
      { vi: "Đổi MK", en: "Change PW", ja: "PW変更" },
      { vi: "Cấp lại mật khẩu", en: "Reset Password", ja: "PW再発行" },
      {
        vi: "Bật camera quét QR",
        en: "Start QR Scanner",
        ja: "QRスキャン起動",
      },
      { vi: "Dừng camera", en: "Stop Camera", ja: "スキャン停止" },
      { vi: "Báo hủy Bobin", en: "Report Scrap", ja: "廃棄申請" },
      { vi: "Khôi phục Bobin", en: "Restore Bobin", ja: "ボビン復帰" },
      { vi: "Xác nhận QC", en: "Confirm QC", ja: "QC確定" },
      { vi: "Xác nhận đã kiểm tra", en: "Confirm QC Checked", ja: "QC検査確定" },
      { vi: 'Đổi thành "ĐIỀU CHỈNH"', en: 'Change to "ADJUSTMENT"', ja: "「調整」へ変更" },
      { vi: "Xác nhận hoàn thành", en: "Confirm Finish", ja: "完了確定" },
      { vi: "Sao chép dữ liệu", en: "Copy Data", ja: "データコピー" },
      { vi: "Xác nhận đã nhập báo cáo", en: "Confirm Report Entered", ja: "日報入力済を確認" },
      { vi: "Bắt đầu nhập dữ liệu", en: "Start Import", ja: "インポート開始" },
      { vi: "Khôi phục về 123", en: "Reset to 123", ja: "123にリセット" },
      { vi: "Lưu nhân viên", en: "Save Employee", ja: "従業員を保存" },
      { vi: "Cập nhật thông tin", en: "Update Info", ja: "情報を更新" },
      { vi: "Trước", en: "Prev", ja: "前へ" },
      { vi: "Sau", en: "Next", ja: "次へ" },
    ],

    // Status badges
    statusBadges: [
      { vi: "CHƯA KIỂM TRA QC", en: "UNCHECKED QC", ja: "QC未検査" },
      { vi: "ĐÃ KIỂM TRA QC", en: "QC CHECKED", ja: "QC検査済" },
      { vi: "ĐANG CHỜ HỦY", en: "PENDING CANCELLATION", ja: "廃棄待ち" },
      { vi: "ĐÃ HỦY", en: "CANCELLED", ja: "廃棄済" },
      { vi: "ĐÃ CUỘN", en: "ROLLED", ja: "巻取済" },
      { vi: "Tất cả", en: "All", ja: "すべて" },
      { vi: "Đã cuộn", en: "Ready (Finished)", ja: "巻取済" },
      { vi: "Đã đùn", en: "Extruded", ja: "押出済" },
      { vi: "Chưa QC", en: "Unchecked", ja: "未検査" },
      { vi: "Đang QC", en: "QC Checked", ja: "QC済" },
      { vi: "Đã QC", en: "QC Checked", ja: "QC済" },
      { vi: "Tồn line", en: "On Line", ja: "ライン滞留" },
      { vi: "Chờ hủy", en: "Pending Scrap", ja: "廃棄待ち" },
      { vi: "Đã hủy", en: "Scrapped", ja: "廃棄済" },
      { vi: "Đang làm việc", en: "Active", ja: "在職中" },
      { vi: "Đã nghỉ việc", en: "Inactive", ja: "退職/ロック" },
      { vi: "Đã nghỉ việc / Khóa", en: "Inactive / Locked", ja: "退職/ロック" },
      { vi: "Mặc định (123)", en: "Default (123)", ja: "初期値 (123)" },
      { vi: "Đã mã hóa", en: "Encrypted Hash", ja: "暗号化済" },
      {
        vi: "Đã đổi MK riêng",
        en: "Custom Password Set",
        ja: "パスワード変更済",
      },
      {
        vi: "Chưa đổi (Bắt buộc)",
        en: "Pending (Required)",
        ja: "未変更（必須）",
      },
    ],
  };

  // 3. ĐỌC NGÔN NGỮ HIỆN TẠI TỪ COOKIE / STORAGE
  function getSavedLanguage() {
    const match = document.cookie.match(new RegExp("(^| )app_lang=([^;]+)"));
    if (match && DICT[match[2]]) {
      return match[2];
    }
    const local = localStorage.getItem("app_lang");
    if (local && DICT[local]) {
      return local;
    }
    return "vi";
  }

  let currentLang = getSavedLanguage();

  // 4. HÀM DỊCH CHUỖI TOÀN CỤC
  window.t = function (key, defaultVal) {
    if (!key) return defaultVal !== undefined ? defaultVal : "";

    // Alias tương đương giữa btn_confirm <-> confirm và btn_cancel <-> cancel
    let aliasKey = null;
    if (key === "btn_confirm") aliasKey = "confirm";
    else if (key === "confirm") aliasKey = "btn_confirm";
    else if (key === "btn_cancel") aliasKey = "cancel";
    else if (key === "cancel") aliasKey = "btn_cancel";

    // 1. Ưu tiên tra cứu trong từ điển tùy biến động (custom translations)
    if (window.__CUSTOM_I18N__) {
      if (
        window.__CUSTOM_I18N__[currentLang] &&
        window.__CUSTOM_I18N__[currentLang][key] !== undefined
      ) {
        return window.__CUSTOM_I18N__[currentLang][key];
      }
      if (
        aliasKey &&
        window.__CUSTOM_I18N__[currentLang] &&
        window.__CUSTOM_I18N__[currentLang][aliasKey] !== undefined
      ) {
        return window.__CUSTOM_I18N__[currentLang][aliasKey];
      }
      if (
        window.__CUSTOM_I18N__["vi"] &&
        window.__CUSTOM_I18N__["vi"][key] !== undefined
      ) {
        return window.__CUSTOM_I18N__["vi"][key];
      }
      if (
        aliasKey &&
        window.__CUSTOM_I18N__["vi"] &&
        window.__CUSTOM_I18N__["vi"][aliasKey] !== undefined
      ) {
        return window.__CUSTOM_I18N__["vi"][aliasKey];
      }
    }

    // 2. Tra cứu trong từ điển mặc định DICT
    if (DICT[currentLang] && DICT[currentLang][key] !== undefined) {
      return DICT[currentLang][key];
    }
    if (
      aliasKey &&
      DICT[currentLang] &&
      DICT[currentLang][aliasKey] !== undefined
    ) {
      return DICT[currentLang][aliasKey];
    }
    if (DICT["vi"] && DICT["vi"][key] !== undefined) {
      return DICT["vi"][key];
    }
    if (aliasKey && DICT["vi"] && DICT["vi"][aliasKey] !== undefined) {
      return DICT["vi"][aliasKey];
    }

    return defaultVal !== undefined ? defaultVal : key;
  };

  window.getAppLanguage = function () {
    return currentLang;
  };

  // Biến cờ kiểm soát tránh MutationObserver lặp vô tận (Infinite loop guard)
  let isTranslating = false;
  let observerInstance = null;
  let observerDebounceTimer = null;

  // 5. HÀM THIẾT LẬP NGÔN NGỮ
  window.setAppLanguage = function (lang, reload = true) {
    if (!DICT[lang]) return;
    currentLang = lang;

    // Lưu cookie và localStorage ngay lập tức
    document.cookie = `app_lang=${lang}; path=/; max-age=31536000; SameSite=Lax`;
    localStorage.setItem("app_lang", lang);

    if (reload) {
      // Đồng bộ sang backend Session qua sendBeacon hoặc fetch trước khi reload
      try {
        const syncUrl = `/WEB_BOBIN/public/index.php?url=auth/setLanguage&lang=${lang}`;
        if (navigator.sendBeacon) {
          navigator.sendBeacon(syncUrl);
          window.location.reload();
          return;
        }
      } catch (e) { }

      // Fallback fetch có timeout ngắn để đảm bảo trang reload mượt mà không bị treo
      let reloaded = false;
      const doReload = () => {
        if (!reloaded) {
          reloaded = true;
          window.location.reload();
        }
      };
      const timer = setTimeout(doReload, 300);

      fetch(`/WEB_BOBIN/public/index.php?url=auth/setLanguage&lang=${lang}`, {
        method: "GET",
        headers: { "X-Requested-With": "XMLHttpRequest" },
      })
        .then(() => {
          clearTimeout(timer);
          doReload();
        })
        .catch(() => {
          clearTimeout(timer);
          doReload();
        });
    } else {
      deepTranslateDOM();
      updateLanguageDropdownUI();
    }
  };

  // 6. DỊCH TRIỆT ĐỂ TOÀN BỘ DOM (DEEP TRANSLATION ENGINE)
  function deepTranslateDOM() {
    if (isTranslating) return;
    isTranslating = true;

    try {
      // A. Áp dụng các thẻ có data-i18n trước
      applyDataI18n();

      // B. Dịch tất cả Table Headers <th>
      document.querySelectorAll("th").forEach((th) => {
        if (th.hasAttribute("data-i18n") || th.querySelector("[data-i18n]"))
          return;
        const raw = th.textContent.trim();
        for (const item of DOM_MAPPINGS.headers) {
          if (raw === item.vi || raw === item.en || raw === item.ja) {
            const target = item.key
              ? window.t(item.key, item[currentLang] || item.vi)
              : item[currentLang] || item.vi;
            if (th.textContent !== target) th.textContent = target;
            break;
          }
        }
      });

      // C. Dịch tất cả Form Labels <label>
      document.querySelectorAll("label").forEach((lbl) => {
        if (lbl.hasAttribute("data-i18n") || lbl.querySelector("[data-i18n]"))
          return;
        const reqSpan = lbl.querySelector('.required, span[style*="red"]');
        const hintSpan = lbl.querySelector(".label-hint");
        let raw = lbl.textContent.replace("*", "").trim();
        let clean = raw
          .replace(/:/g, "")
          .replace(/\(mét\)/gi, "")
          .replace(/\(m\)/gi, "")
          .replace(/\(0, 1 hoặc 2\)/gi, "")
          .replace(/\(Tự động ghép mã\)/gi, "")
          .trim();

        for (const item of DOM_MAPPINGS.labels) {
          const itemCleanVi = item.vi.replace(/:/g, "").trim();
          const itemCleanEn = item.en.replace(/:/g, "").trim();
          const itemCleanJa = item.ja.replace(/:/g, "").trim();

          if (
            clean === itemCleanVi ||
            clean === itemCleanEn ||
            clean === itemCleanJa ||
            raw === item.vi ||
            raw === item.en ||
            raw === item.ja
          ) {
            const translated = item.key
              ? window.t(item.key, item[currentLang] || item.vi)
              : item[currentLang] || item.vi;
            let html = translated;
            if (raw.includes(":") || reqSpan) {
              html += ":";
            }
            if (hintSpan) {
              html += " " + hintSpan.outerHTML;
            }
            if (reqSpan) {
              html += ' <span class="required" style="color:#ef4444;">*</span>';
            }
            if (lbl.innerHTML !== html) {
              lbl.innerHTML = html;
            }
            break;
          }
        }
      });

      // D. Dịch tất cả Button, Links thao tác và Status Badges
      document
        .querySelectorAll(
          "button, a.btn-primary, a.btn-filter, a.btn-secondary, a.btn-back-modern, .btn-submit-pwd, .btn-cancel-pwd, .action-btn, .status-btn, .status-badge, .btn-cancel, .btn-submit",
        )
        .forEach((btn) => {
          // Nếu bản thân nút hoặc phần tử con đã có data-i18n, applyDataI18n() đã dịch chính xác theo key, không can thiệp ghi đè
          if (
            btn.hasAttribute("data-i18n") ||
            btn.querySelector("[data-i18n]")
          ) {
            return;
          }

          const raw = btn.textContent.trim();
          for (const item of DOM_MAPPINGS.buttons) {
            // Nếu item là btn_cancel (Hủy/Quay lại trong modal) nhưng nút thực tế chứa Bobin/ボビン -> BỎ QUA để không đè nhầm thành "Trở lại"
            if (
              item.key === "btn_cancel" &&
              (raw.includes("Bobin") || raw.includes("ボビン"))
            ) {
              continue;
            }
            const match =
              raw.includes(item.vi) ||
              raw.includes(item.en) ||
              raw.includes(item.ja) ||
              (item.alias && item.alias.some((al) => raw.includes(al)));
            if (match) {
              // Ưu tiên tra cứu qua window.t nếu item có key liên kết (e.g. btn_confirm, btn_cancel)
              const target = item.key
                ? window.t(item.key, item[currentLang] || item.vi)
                : item[currentLang] || item.vi;
              // Giữ lại icon nếu có SVG
              const svg = btn.querySelector("svg");
              if (svg) {
                // Chỉ cập nhật phần text, không xóa sạch innerHTML làm hỏng listeners
                const textNodes = Array.from(btn.childNodes).filter(
                  (node) => node.nodeType === Node.TEXT_NODE,
                );
                if (textNodes.length > 0) {
                  textNodes[textNodes.length - 1].textContent = " " + target;
                } else {
                  btn.innerHTML = "";
                  btn.appendChild(svg);
                  btn.append(" " + target);
                }
              } else {
                if (btn.textContent !== target) btn.textContent = target;
              }
              break;
            }
          }

          // Dịch Status Buttons & Badges (CHƯA KIỂM TRA QC, ĐÃ KIỂM TRA QC, ĐANG CHỜ HỦY, ĐÃ HỦY, ĐÃ CUỘN...)
          for (const item of DOM_MAPPINGS.statusBadges) {
            if (
              raw.includes(item.vi) ||
              raw.includes(item.en) ||
              raw.includes(item.ja)
            ) {
              const target = item.key
                ? window.t(item.key, item[currentLang] || item.vi)
                : item[currentLang] || item.vi;
              // Giữ lại icon emoji nếu có
              const emojiMatch = raw.match(
                /^([\p{Emoji}\u200d\uFE0F\uFE0E]+|\u2705|\u23F3|\u274C|\uD83D\uDCE6)\s*/u,
              );
              const prefix = emojiMatch ? emojiMatch[0] : "";
              const fullText = prefix + target;
              if (btn.textContent !== fullText) btn.textContent = fullText;
              break;
            }
          }
        });

      // E. Dịch Input Placeholders (Fallback cho các input không gắn thẻ data-i18n-ph)
      document
        .querySelectorAll("input[placeholder], textarea[placeholder]")
        .forEach((input) => {
          if (
            input.hasAttribute("data-i18n-ph") ||
            input.hasAttribute("data-placeholder-i18n") ||
            input.hasAttribute("data-i18n-placeholder") ||
            input.hasAttribute("data-i18n")
          ) {
            return;
          }
          const ph = input.getAttribute("placeholder").trim();
          let newPh = null;
          if (
            ph.includes("Nhập thông tin") ||
            ph.includes("tra cứu") ||
            ph.includes("Search by") ||
            ph.includes("検索")
          ) {
            newPh = window.t("keyword_search");
          } else if (
            ph.includes("quét mã Bobin") ||
            ph.includes("Bobin code") ||
            ph.includes("ボビン番号")
          ) {
            newPh = window.t("ph_scan_bobin");
          } else if (
            ph.includes("Tự động theo mã") ||
            ph.includes("Auto generated by") ||
            ph.includes("Auto by Bobin") ||
            ph.includes("ボビン番号から自動")
          ) {
            newPh = window.t("ph_auto_size");
          } else if (
            ph.includes("tìm mã sản phẩm") ||
            ph.includes("search product") ||
            ph.includes("製品コードを検索")
          ) {
            newPh = window.t("ph_search_product");
          } else if (
            ph.includes("máy đùn") ||
            ph.includes("extruder") ||
            ph.includes("押出機")
          ) {
            newPh = window.t("ph_select_machine");
          } else if (
            ph.includes("loại vật liệu") ||
            ph.includes("material") ||
            ph.includes("材料")
          ) {
            newPh = window.t("ph_select_material");
          } else if (
            ph.includes("0, 1 hoặc 2") ||
            ph.includes("0, 1 or 2") ||
            ph.includes("0, 1または2")
          ) {
            newPh = window.t("ph_grind_hint");
          } else if (
            ph.includes("Lot vật liệu") ||
            ph.includes("material lot") ||
            ph.includes("材料ロット")
          ) {
            newPh = window.t("ph_enter_material_lot");
          } else if (
            ph.includes("Tự động ghép mã") ||
            ph.includes("Lot in được tạo") ||
            ph.includes("Auto merged") ||
            ph.includes("印字ロット自動")
          ) {
            newPh = window.t("ph_auto_printlot");
          } else if (
            ph.includes("Rack đặt") ||
            ph.includes("Rack location") ||
            ph.includes("ラック位置") ||
            ph.includes("mã Rack")
          ) {
            newPh = window.t("ph_select_rack");
          } else if (
            ph.includes("Nhập mã số nhân viên") ||
            ph.includes("employee ID") ||
            ph.includes("社員番号")
          ) {
            newPh = window.t("login_username_ph");
          } else if (
            ph.includes("Nhập mật khẩu") ||
            ph.includes("password") ||
            ph.includes("パスワード")
          ) {
            newPh = window.t("login_password_ph");
          } else if (
            ph.includes("Tối thiểu 6 ký tự") ||
            ph.includes("At least 6") ||
            ph.includes("6文字以上")
          ) {
            newPh = window.t("cp_new_pwd_ph");
          } else if (
            ph.includes("Nhập lại mật khẩu") ||
            ph.includes("Re-enter") ||
            ph.includes("再入力")
          ) {
            newPh = window.t("cp_confirm_pwd_ph");
          } else if (
            ph.includes("Ghi chú ngoại quan") ||
            ph.includes("inspection note") ||
            ph.includes("外観検査メモ")
          ) {
            newPh = window.t("qc_note_ph");
          } else if (
            ph.includes("giải trình lý do") ||
            ph.includes("explanatory note") ||
            ph.includes("理由の説明")
          ) {
            newPh = window.t("qc_note_explain_ph");
          } else if (
            ph.includes("máy cuộn") ||
            ph.includes("winding machine") ||
            ph.includes("巻取機")
          ) {
            newPh = window.t("ph_winding_machine_input");
          } else if (
            ph.includes("Ghi chú cuộn") ||
            ph.includes("bất thường") ||
            ph.includes("abnormal") ||
            ph.includes("巻取備考")
          ) {
            newPh = window.t("ph_winding_note_explain");
          } else if (
            ph.includes("Mã NV hoặc Họ tên") ||
            ph.includes("ID or Name") ||
            ph.includes("氏名で検索")
          ) {
            newPh = window.t("emp_search_ph");
          } else if (
            ph.includes("02619486") ||
            ph.includes("NV01") ||
            ph.includes("EMP01")
          ) {
            newPh = window.t("emp_code_ph");
          } else if (
            ph.includes("Nguyễn Văn A") ||
            ph.includes("John Smith") ||
            ph.includes("田中太郎")
          ) {
            newPh = window.t("emp_name_ph");
          } else if (ph.includes("A00330") || ph.includes("A00340")) {
            newPh = window.t("emp_cost_center_ph");
          } else if (
            ph.includes("Mặc định 123") ||
            ph.includes("Default 123") ||
            ph.includes("デフォルト123")
          ) {
            newPh = window.t("emp_reset_custom_ph");
          } else if (
            ph.includes("tên để tìm") ||
            ph.includes("name to search") ||
            ph.includes("名前を入力")
          ) {
            newPh = window.t("perm_search_placeholder");
          } else if (
            ph.includes("từ khóa") ||
            ph.includes("keyword key") ||
            ph.includes("キーワードコード")
          ) {
            newPh = window.t("lang_search_ph");
          } else if (ph.includes("BB29099")) {
            newPh = window.t("ph_bobin_id_example");
          } else if (
            ph.includes("QR sẽ hiển thị") ||
            ph.includes("QR code will appear") ||
            ph.includes("QRコードがここに表示")
          ) {
            newPh = window.t("ph_qr_scan_result");
          } else if (ph.includes("1500")) {
            newPh = window.t("ph_length_hint");
          }
          if (newPh && ph !== newPh) {
            input.setAttribute("placeholder", newPh);
          }
        });

      // F. Dịch Menu-bar Links
      document.querySelectorAll(".menu-left a, .menu-bar a").forEach((a) => {
        const text = a.textContent.trim();
        if (
          text.includes("Nhóm đùn") ||
          text.includes("Extrusion") ||
          text.includes("押出")
        ) {
          if (
            text.includes("Điều chỉnh") ||
            text.includes("Edit") ||
            text.includes("編集")
          ) {
            const target = window.t("nav_extrusion_edit");
            if (a.textContent !== target) a.textContent = target;
          } else {
            const target = window.t("nav_extrusion");
            if (a.textContent !== target) a.textContent = target;
          }
        } else if (
          text.includes("QC") ||
          text.includes("QC Check") ||
          text.includes("QC検査")
        ) {
          if (
            text.includes("Điều chỉnh") ||
            text.includes("Edit") ||
            text.includes("編集")
          ) {
            const target = window.t("nav_qc_edit");
            if (a.textContent !== target) a.textContent = target;
          } else {
            const target = window.t("nav_qc");
            if (a.textContent !== target) a.textContent = target;
          }
        } else if (
          text.includes("Cuộn") ||
          text.includes("Winding") ||
          text.includes("巻取")
        ) {
          if (
            text.includes("Điều chỉnh") ||
            text.includes("Edit") ||
            text.includes("編集")
          ) {
            const target = window.t("nav_winding_edit");
            if (a.textContent !== target) a.textContent = target;
          } else {
            const target = window.t("nav_winding");
            if (a.textContent !== target) a.textContent = target;
          }
        } else if (
          text.includes("Danh sách Bobin") ||
          text.includes("Bobin List") ||
          text.includes("ボビン一覧")
        ) {
          const target = window.t("nav_bobin_list");
          if (a.textContent !== target) a.textContent = target;
        } else if (
          text.includes("Lịch sử Bobin") ||
          text.includes("Bobin History") ||
          text.includes("ボビン履歴")
        ) {
          const target = window.t("nav_bobin_history");
          if (a.textContent !== target) a.textContent = target;
        } else if (
          text.includes("chờ hủy") ||
          text.includes("Pending") ||
          text.includes("廃棄待ち")
        ) {
          const span = a.querySelector('[data-i18n="nav_pending_cancel"]');
          const badge = a.querySelector(".badge-pending-count");
          const targetText = window.t("nav_pending_cancel");
          if (span) {
            if (span.textContent !== targetText) span.textContent = targetText;
          } else {
            const badgeText = badge ? badge.outerHTML : "";
            a.innerHTML =
              '<span data-i18n="nav_pending_cancel">' +
              targetText +
              "</span> " +
              badgeText;
          }
        } else if (
          text.includes("nhân viên") ||
          text.includes("Employee") ||
          text.includes("従業員")
        ) {
          const target = window.t("nav_employee_list");
          if (a.textContent !== target) a.textContent = target;
        }
      });

      // Nút Đổi MK & Đăng xuất
      document.querySelectorAll(".btn-change-pwd").forEach((btn) => {
        const target = window.t("nav_change_pwd");
        if (btn.textContent !== target) btn.textContent = target;
      });
      document
        .querySelectorAll(".logout-btn, .btn-nav-logout")
        .forEach((btn) => {
          const target = window.t("nav_logout");
          if (btn.textContent !== target) btn.textContent = target;
        });

      // G. Dịch các phần tử đặc thù của trang nhân viên
      document.querySelectorAll(".badge-self").forEach((el) => {
        const target = window.t("emp_self_badge");
        if (el.textContent !== target) el.textContent = target;
      });
      document.querySelectorAll(".profile-label").forEach((el) => {
        if (
          el.textContent.includes("Mã số") ||
          el.textContent.includes("Employee ID") ||
          el.textContent.includes("社員番号")
        ) {
          const target = window.t("emp_profile_label");
          if (el.textContent !== target) el.textContent = target;
        }
      });
      document.querySelectorAll(".target-sub").forEach((el) => {
        if (
          el.textContent.includes("cần cấp") ||
          el.textContent.includes("reset") ||
          el.textContent.includes("再発行")
        ) {
          const target = window.t("emp_reset_target");
          if (el.textContent !== target) el.textContent = target;
        }
      });
      // Chart legend labels
      document.querySelectorAll("[data-chart-role]").forEach((el) => {
        const role = el.getAttribute("data-chart-role");
        const keyMap = {
          extrusion: "emp_role_extrusion",
          qc: "emp_role_qc",
          winding: "emp_role_winding",
          admin: "emp_role_admin",
        };
        if (keyMap[role]) {
          const target = window.t(keyMap[role]);
          if (el.textContent !== target) el.textContent = target;
        }
      });

      // H. Dịch các Section Title, Section Desc và Checkbox Labels
      document
        .querySelectorAll(".section-title, .section-desc, .check-box-label")
        .forEach((el) => {
          const raw = el.textContent.trim();
          for (const item of DOM_MAPPINGS.sections || []) {
            if (raw === item.vi || raw === item.en || raw === item.ja) {
              const target = item[currentLang] || item.vi;
              if (el.textContent !== target) el.textContent = target;
              return;
            }
          }
          for (const item of DOM_MAPPINGS.labels || []) {
            if (raw === item.vi || raw === item.en || raw === item.ja) {
              const target = item[currentLang] || item.vi;
              if (el.textContent !== target) el.textContent = target;
              return;
            }
          }
        });

      // I. Dịch phân trang (Pagination .page-btn, .page-info)
      document.querySelectorAll(".page-btn").forEach((btn) => {
        const text = btn.textContent.trim();
        if (
          text.includes("Trước") ||
          text.includes("Prev") ||
          text.includes("前へ")
        ) {
          const target = window.t("page_prev", "‹ Trước");
          if (btn.textContent.trim() !== target) btn.textContent = target;
        } else if (
          text.includes("Sau") ||
          text.includes("Next") ||
          text.includes("次へ")
        ) {
          const target = window.t("page_next", "Sau ›");
          if (btn.textContent.trim() !== target) btn.textContent = target;
        }
      });

      document.querySelectorAll(".page-info").forEach((info) => {
        const text = info.textContent.trim();
        // Khớp mẫu: Trang X / Y (Tổng Z Bobin) hoặc Page X / Y (Total Z Bobins) hoặc ページ X / Y (全 Z ボビン)
        const m = text.match(
          /(?:Trang|Page|ページ)\s*(\d+)\s*\/\s*(\d+)\s*(?:\([^0-9]*([\d,]+)[^)]*\))?/i,
        );
        if (m) {
          const current = m[1];
          const total = m[2];
          const records = m[3] || "0";
          let tmpl = window.t(
            "page_info",
            "Trang {current} / {total} (Tổng {records} Bobin)",
          );
          tmpl = tmpl
            .replace("{current}", current)
            .replace(":current", current)
            .replace("{total}", total)
            .replace(":total", total)
            .replace("{records}", records)
            .replace(":records", records);
          if (info.textContent.trim() !== tmpl) {
            info.textContent = tmpl;
          }
        }
      });

      // J. Dịch sâu các thẻ <option>, Giá trị mặc định (Chưa cập nhật / Loại Bobin / Ca / Thông khí), KPI, Filter, Empty State và Hộp thoại động
      const VALUE_TRANSLATIONS = [
        { vi: "Chưa cập nhật", en: "Not updated", ja: "未更新" },
        { vi: "Chưa kiểm", en: "Unchecked", ja: "未検査" },
        { vi: "Chưa rõ", en: "Unknown", ja: "不明" },
        { vi: "Không có ghi chú", en: "No remarks", ja: "備考なし" },
        { vi: "Không có", en: "None", ja: "なし" },
        { vi: "Chưa có", en: "None", ja: "なし" },
        { vi: "Thành công", en: "Pass", ja: "合格" },
        { vi: "Thất bại", en: "Fail", ja: "不合格" },
        { vi: "💨 Thành công (Pass)", en: "💨 Pass", ja: "💨 合格 (Pass)" },
        { vi: "⚠️ Thất bại (Fail)", en: "⚠️ Fail", ja: "⚠️ 不合格 (Fail)" },
        { vi: "Hành chính", en: "Regular Shift", ja: "日勤(常昼)" },
        { vi: "Ca 1", en: "Shift 1", ja: "1直" },
        { vi: "Ca 2", en: "Shift 2", ja: "2直" },
        { vi: "Ca 3", en: "Shift 3", ja: "3直" },
        { vi: "Sản xuất", en: "Production", ja: "通常生産" },
        { vi: "Bù", en: "Compensation", ja: "補填" },
        { vi: "Điều chỉnh (Do CP)", en: "Adjustment (By CP)", ja: "調整 (CP起因)" },
        { vi: "Điều chỉnh (Ngoại quan: Gel)", en: "Adjustment (Visual: Gel)", ja: "調整 (外観: ゲル)" },
        { vi: "Điều chỉnh (Ngoại quan: Dị vật)", en: "Adjustment (Visual: Foreign Object)", ja: "調整 (外観: 異物)" },
        { vi: "Điều chỉnh (Ngoại quan: Trầy)", en: "Adjustment (Visual: Scratch)", ja: "調整 (外観: 傷)" },
        { vi: "Điều chỉnh (Ngoại quan: Biến dạng)", en: "Adjustment (Visual: Deformation)", ja: "調整 (外観: 変形)" },
        { vi: "Điều chỉnh (Ngoại quan: Xước)", en: "Adjustment (Visual: Scuff)", ja: "調整 (外観: 擦れ)" },
        { vi: "Điều chỉnh (Ngoại quan: Chữ in)", en: "Adjustment (Visual: Print)", ja: "調整 (外観: 印字)" },
        { vi: "Điều chỉnh (Ngoại quan: Vón cục)", en: "Adjustment (Visual: Lump)", ja: "調整 (外観: 塊)" },
        { vi: "Điều chỉnh (Ngoại quan: Màu)", en: "Adjustment (Visual: Color)", ja: "調整 (外観: 色調)" },
        { vi: "📦 Tất cả trạng thái", en: "📦 All Statuses", ja: "📦 すべての状態" },
        { vi: "⚙️ ĐÃ ĐÙN", en: "⚙️ EXTRUDED", ja: "⚙️ 押出済" },
        { vi: "⏳ CHƯA KIỂM TRA QC", en: "⏳ UNCHECKED QC", ja: "⏳ QC未検査" },
        { vi: "⏳ Chưa QC", en: "⏳ Unchecked QC", ja: "⏳ QC未検査" },
        { vi: "🛡️ Đã QC", en: "🛡️ QC Checked", ja: "🛡️ QC検査済" },
        { vi: "🛡️ ĐÃ KIỂM TRA QC (Tất cả)", en: "🛡️ QC CHECKED (All)", ja: "🛡️ QC検査済 (すべて)" },
        { vi: "🛡️ ĐÃ KT QC (Đùn trong khoảng)", en: "🛡️ QC CHECKED (Extruded in period)", ja: "🛡️ QC検査済 (期間内押出)" },
        { vi: "🛡️ ĐÃ KT QC (Đùn trước đó)", en: "🛡️ QC CHECKED (Extruded earlier)", ja: "🛡️ QC検査済 (期間前押出)" },
        { vi: "✅ Đã cuộn", en: "✅ Rolled", ja: "✅ 巻取済" },
        { vi: "✅ ĐÃ CUỘN", en: "✅ ROLLED", ja: "✅ 巻取済" },
        { vi: "📍 Tồn line", en: "📍 On Line", ja: "📍 ライン滞留" },
        { vi: "⌛ Chờ hủy", en: "⌛ Pending Scrap", ja: "⌛ 廃棄待ち" },
        { vi: "⚠️ CHỜ HỦY (Tất cả)", en: "⚠️ PENDING SCRAP (All)", ja: "⚠️ 廃棄待ち (すべて)" },
        { vi: "⚠️ CHỜ HỦY (Đùn trong khoảng)", en: "⚠️ PENDING SCRAP (Extruded in period)", ja: "⚠️ 廃棄待ち (期間内押出)" },
        { vi: "⚠️ CHỜ HỦY (Đùn trước đó)", en: "⚠️ PENDING SCRAP (Extruded earlier)", ja: "⚠️ 廃棄待ち (期間前押出)" },
        { vi: "🗑️ ĐÃ HỦY (Tất cả)", en: "🗑️ SCRAPPED (All)", ja: "🗑️ 廃棄済 (すべて)" },
        { vi: "🗑️ ĐÃ HỦY (Đùn trong khoảng)", en: "🗑️ SCRAPPED (Extruded in period)", ja: "🗑️ 廃棄済 (期間内押出)" },
        { vi: "🗑️ ĐÃ HỦY (Đùn trước đó)", en: "🗑️ SCRAPPED (Extruded earlier)", ja: "🗑️ 廃棄済 (期間前押出)" },
        { vi: "Tất cả kích thước", en: "All Sizes", ja: "すべてのサイズ" },
        { vi: "Mọi kích thước", en: "All Sizes", ja: "すべてのサイズ" },
        { vi: "Tất cả loại Bobin", en: "All Bobin Types", ja: "すべてのボビン種別" },
        { vi: "Mọi loại Bobin", en: "All Bobin Types", ja: "すべてのボビン種別" },
        { vi: "Tất cả các Rack", en: "All Racks", ja: "すべてのラック" },
        { vi: "🏢 B021 => Xưởng 2, tầng 1", en: "🏢 B021 => Workshop 2, Floor 1", ja: "🏢 B021 => 第2工場 1階" },
        { vi: "🏢 B022 => Xưởng 2, tầng 2", en: "🏢 B022 => Workshop 2, Floor 2", ja: "🏢 B022 => 第2工場 2階" },
        { vi: "🏢 B031 => Xưởng 3, tầng 1", en: "🏢 B031 => Workshop 3, Floor 1", ja: "🏢 B031 => 第3工場 1階" },
        { vi: "🏢 B032 => Xưởng 3, tầng 2", en: "🏢 B032 => Workshop 3, Floor 2", ja: "🏢 B032 => 第3工場 2階" },
        { vi: "Kiểm tra QC (QC Check)", en: "QC Inspection (QC Check)", ja: "QC検査 (QC Check)" },
        { vi: "Chi tiết danh sách Bobin", en: "Bobin Detail List", ja: "ボビン詳細一覧" },
        { vi: "Lịch sử Bobin", en: "Bobin History", ja: "ボビン履歴" },
        { vi: "Xác nhận Cuộn (Winding)", en: "Winding Confirmation", ja: "巻取完了確認 (Winding)" },
        { vi: "Tổng số lượng Bobin", en: "Total Bobins", ja: "ボビン総数" },
        { vi: "Tổng số lượng Bobin thực tế", en: "Actual Total Bobins", ja: "実ボビン総数" },
        { vi: "Bobin Trống", en: "Empty Bobins", ja: "空きボビン" },
        { vi: "BOBIN TRỐNG", en: "EMPTY BOBINS", ja: "空きボビン" },
        { vi: "ĐÃ ĐÙN", en: "EXTRUDED", ja: "押出済" },
        { vi: "CHƯA KT QC", en: "UNCHECKED QC", ja: "QC未検査" },
        { vi: "ĐÃ KT QC", en: "QC CHECKED", ja: "QC検査済" },
        { vi: "CHỜ HỦY", en: "PENDING SCRAP", ja: "廃棄待ち" },
        { vi: "📌 Trạng thái:", en: "📌 Status:", ja: "📌 状態:" },
        { vi: "📏 Kích thước:", en: "📏 Size:", ja: "📏 サイズ:" },
        { vi: "🏷️ Loại Bobin:", en: "🏷️ Bobin Type:", ja: "🏷️ ボビン種別:" },
        { vi: "🏢 Vị trí Rack:", en: "🏢 Rack Location:", ja: "🏢 ラック位置:" },
        { vi: "🔄 Đặt lại bộ lọc", en: "🔄 Reset Filter", ja: "🔄 フィルタリセット" },
        { vi: "Xuất toàn bộ kết quả lọc", en: "Export All Filtered Results", ja: "絞り込み結果をすべて出力" },
        { vi: "Xuất các Bobin đã chọn", en: "Export Selected Bobins", ja: "選択したボビンを出力" },
        { vi: "Xuất các bản ghi đã chọn", en: "Export Selected Records", ja: "選択した履歴を出力" },
        { vi: "🏭 Đùn Check", en: "🏭 Extrusion Check", ja: "🏭 押出チェック" },
        { vi: "🏭 Đùn Check (Tự kiểm tra)", en: "🏭 Extrusion Self-Check", ja: "🏭 押出自己検査" },
        { vi: "🏭 Đùn tự kiểm tra:", en: "🏭 Extrusion Self-Check:", ja: "🏭 押出自己検査:" },
        { vi: "🛡️ QC đánh giá:", en: "🛡️ QC Evaluation:", ja: "🛡️ QC評価:" },
        { vi: "🛡️ Kiểm tra ngoại quan QC", en: "🛡️ QC Visual Inspection", ja: "🛡️ QC外観検査" },
        { vi: "📍 Thông tin cuộn", en: "📍 Winding Info", ja: "📍 巻取情報" },
        { vi: "📍 Ghi nhận thông tin công đoạn Cuộn", en: "📍 Record Winding Stage Info", ja: "📍 巻取工程情報の記録" },
        { vi: "Kết quả kiểm tra ngoại quan:", en: "Visual Inspection Result:", ja: "外観検査結果:" },
        { vi: 'Đổi loại Bobin sang "Điều chỉnh"', en: 'Change Bobin Type to "Adjustment"', ja: "ボビン種別を「調整」へ変更" },
        { vi: "Vui lòng chọn loại điều chỉnh cụ thể:", en: "Please select the specific adjustment type:", ja: "具体的な調整種別を選択してください：" },
        { vi: "Hiện tại không có Bobin nào đang chờ kiểm tra QC.", en: "No Bobins are currently waiting for QC inspection.", ja: "現在QC検査待ちのボビンはありません。" },
        { vi: "Hiện tại không có Bobin nào đang chờ cuộn.", en: "No Bobins are currently waiting for winding.", ja: "現在巻取待ちのボビンはありません。" },
        { vi: "Hiện tại không có Bobin nào trong danh sách chờ hủy.", en: "No Bobins are currently in the pending cancellation list.", ja: "現在廃棄待ちのボビンはありません。" },
        { vi: "Không tìm thấy Bobin nào khớp với bộ lọc.", en: "No Bobins found matching the current filter.", ja: "条件に一致するボビンが見つかりません。" },
        { vi: "Không tìm thấy dữ liệu nào trong khoảng thời gian này.", en: "No data found in the selected time period.", ja: "指定期間内のデータが見つかりません。" },
        { vi: "Danh sách trống", en: "Empty list", ja: "データがありません" },
      ];

      // J.1 Dịch <option> và <optgroup> (chỉ dịch text hiển thị, giữ nguyên value)
      document.querySelectorAll("option").forEach((opt) => {
        if (opt.hasAttribute("data-i18n")) return;
        const raw = opt.textContent.trim();
        for (const item of VALUE_TRANSLATIONS) {
          if (raw === item.vi || raw === item.en || raw === item.ja) {
            const target = item[currentLang] || item.vi;
            if (opt.textContent !== target) opt.textContent = target;
            break;
          }
        }
      });

      // J.2 Dịch các phần tử hiển thị giá trị, KPI, Filter, Empty State, Strip Header
      document
        .querySelectorAll(
          ".val-sub, .bobin-type-pill, .bobin-highlight-printlot, .pipeline-submeta, .kpi-card .label, .kpi-name, .filter-label, .btn-reset-filter, .export-dropdown-menu strong, .qc-title, .winding-title, .pipeline-title, .strip-label, .ext-title, .qc-switches-label, .empty-state, #modalChangeType h3, #modalChangeType p, .type-toggle-label",
        )
        .forEach((el) => {
          if (el.hasAttribute("data-i18n") || el.querySelector("[data-i18n]"))
            return;
          const raw = el.textContent.trim();
          for (const item of VALUE_TRANSLATIONS) {
            if (raw === item.vi || raw === item.en || raw === item.ja) {
              const target = item[currentLang] || item.vi;
              if (el.textContent !== target) el.textContent = target;
              break;
            }
          }
        });

      // J.3 Dịch các chip kiểm tra ngoại quan (.vi-badge, .ext-chip, .qc-chip)
      const DEFECT_NAMES = [
        { vi: "Đường kính", en: "Diameter", ja: "外径" },
        { vi: "Đ.Kính", en: "Dia.", ja: "外径" },
        { vi: "Gel", en: "Gel", ja: "ゲル" },
        { vi: "Dị vật", en: "Foreign Obj", ja: "異物" },
        { vi: "Màu sắc", en: "Color", ja: "色調" },
        { vi: "Màu", en: "Color", ja: "色調" },
        { vi: "Chữ in", en: "Print", ja: "印字" },
      ];
      document.querySelectorAll(".vi-badge, .ext-chip").forEach((chip) => {
        const raw = chip.textContent.trim();
        const parts = raw.split(":");
        if (parts.length === 2) {
          const namePart = parts[0].trim();
          const statusPart = parts[1].trim();
          for (const d of DEFECT_NAMES) {
            if (
              namePart === d.vi ||
              namePart === d.en ||
              namePart === d.ja
            ) {
              const target = `${d[currentLang] || d.vi}: ${statusPart}`;
              if (chip.textContent !== target) chip.textContent = target;
              break;
            }
          }
        }
      });
      document.querySelectorAll(".qc-chip > span:first-child").forEach((sp) => {
        const raw = sp.textContent.trim();
        for (const d of DEFECT_NAMES) {
          if (raw === d.vi || raw === d.en || raw === d.ja) {
            const target = d[currentLang] || d.vi;
            if (sp.textContent !== target) sp.textContent = target;
            break;
          }
        }
      });
    } finally {
      // Giải phóng cờ sau microtask để MutationObserver không bị kích hoạt chéo
      setTimeout(() => {
        isTranslating = false;
      }, 50);
    }
  }

  // Hàm dịch chuỗi thông báo động (Toast / Dialog runtime)
  const DYNAMIC_MSG_MAPPINGS = [
    {
      vi: "Vui lòng hoàn thành hoặc hủy Bobin đang sửa trước!",
      en: "Please finish or cancel the Bobin currently being edited first!",
      ja: "現在編集中のボビンを先に完了またはキャンセルしてください！",
    },
    {
      vi: "Đã hủy bỏ thay đổi",
      en: "Changes discarded",
      ja: "変更を取り消しました",
    },
    {
      vi: "Cập nhật thành công!",
      en: "Updated successfully!",
      ja: "更新に成功しました！",
    },
    {
      vi: "Cập nhật thất bại",
      en: "Update failed",
      ja: "更新に失敗しました",
    },
    {
      vi: "Đã xảy ra lỗi kết nối hệ thống!",
      en: "System connection error occurred!",
      ja: "システム接続エラーが発生しました！",
    },
    {
      vi: "Đã xảy ra lỗi kết nối!",
      en: "Connection error occurred!",
      ja: "接続エラーが発生しました！",
    },
    {
      vi: "Không có nội dung để copy!",
      en: "No content to copy!",
      ja: "コピーする内容がありません！",
    },
    {
      vi: "Đã copy nội dung!",
      en: "Copied to clipboard!",
      ja: "クリップボードにコピーしました！",
    },
    {
      vi: "Đã sao chép thành công!",
      en: "Copied successfully!",
      ja: "正常にコピーしました！",
    },
    {
      vi: "Không thể copy nội dung!",
      en: "Unable to copy content!",
      ja: "内容をコピーできませんでした！",
    },
    {
      vi: "Vui lòng tích chọn ít nhất 1 Bobin trong danh sách trước khi xuất!",
      en: "Please select at least 1 Bobin from the list before exporting!",
      ja: "出力する前に少なくとも1つのボビンを選択してください！",
    },
    {
      vi: "Chưa chọn hạng mục phán định là NG!",
      en: "Please select at least 1 NG visual defect before cancelling!",
      ja: "廃棄するには少なくとも1つのNG項目を選択してください！",
    },
    {
      vi: "Vui lòng nhập lý do hủy vào phần Ghi chú QC!",
      en: "Please enter the cancellation reason in QC Notes!",
      ja: "QC備考欄に廃棄理由を入力してください！",
    },
    {
      vi: "Vui lòng nhập Mã số nhân viên QC trước khi xác nhận!",
      en: "Please enter QC Inspector ID before confirming!",
      ja: "確定する前にQC検査員番号を入力してください！",
    },
    {
      vi: "Vui lòng nhập Mã số nhân viên QC trước khi hủy!",
      en: "Please enter QC Inspector ID before cancelling!",
      ja: "廃棄する前にQC検査員番号を入力してください！",
    },
    {
      vi: "Vui lòng nhập Họ tên nhân viên QC trước khi hủy!",
      en: "Please enter QC Inspector Name before cancelling!",
      ja: "廃棄する前にQC検査員氏名を入力してください！",
    },
    {
      vi: "Vui lòng nhập hoặc chọn Máy cuộn!",
      en: "Please enter or select a Winding machine!",
      ja: "巻取機を入力または選択してください！",
    },
    {
      vi: "Vui lòng nhập máy cuộn!",
      en: "Please enter a Winding machine!",
      ja: "巻取機を入力してください！",
    },
    {
      vi: "Vui lòng nhập Mã nhân viên cuộn!",
      en: "Please enter Winding Operator ID!",
      ja: "巻取作業者番号を入力してください！",
    },
  ];

  window.translateDynamicText = function (rawText) {
    if (!rawText || typeof rawText !== "string") return rawText;
    if (currentLang === "vi") return rawText;
    const cleaned = rawText
      .replace(/^([✅❌⚠️ℹ️⚡💬]|Lỗi:|Lỗi\s*:)\s*/u, "")
      .trim();
    for (const item of DYNAMIC_MSG_MAPPINGS) {
      if (cleaned === item.vi || rawText.trim() === item.vi) {
        return item[currentLang] || rawText;
      }
    }
    return rawText;
  };

  // Áp dụng thuộc tính data-i18n rõ ràng
  function applyDataI18n() {
    document.querySelectorAll("[data-i18n]").forEach((el) => {
      let key = el.getAttribute("data-i18n");
      if (!key) return;

      let targetAttr = null;
      const attrMatch = key.match(/^\[([a-zA-Z0-9_-]+)\](.*)$/);
      if (attrMatch) {
        targetAttr = attrMatch[1];
        key = attrMatch[2];
      }

      const translation = window.t(key);
      if (
        translation !== undefined &&
        translation !== null &&
        translation !== ""
      ) {
        if (targetAttr) {
          if (el.getAttribute(targetAttr) !== translation) {
            el.setAttribute(targetAttr, translation);
          }
          return;
        }

        if (el.tagName === "INPUT" || el.tagName === "TEXTAREA") {
          if (el.hasAttribute("placeholder")) {
            if (el.getAttribute("placeholder") !== translation) {
              el.setAttribute("placeholder", translation);
            }
          }
        } else {
          const badge = el.querySelector(".badge-pending-count");
          if (badge) {
            // Bảo vệ không re-render HTML nếu nội dung đã chính xác
            const span = el.querySelector("[data-i18n-text]");
            if (span) {
              if (span.textContent !== translation)
                span.textContent = translation;
            } else {
              const badgeHtml = badge.outerHTML;
              const newHtml = `<span data-i18n-text="1">${translation}</span> ${badgeHtml}`;
              if (el.innerHTML !== newHtml) {
                el.innerHTML = newHtml;
              }
            }
          } else {
            const svg = el.querySelector("svg");
            if (svg) {
              const textNodes = Array.from(el.childNodes).filter(
                (node) => node.nodeType === Node.TEXT_NODE,
              );
              if (textNodes.length > 0) {
                textNodes[textNodes.length - 1].textContent = " " + translation;
              } else {
                el.innerHTML = "";
                el.appendChild(svg);
                el.append(" " + translation);
              }
            } else {
              if (el.textContent !== translation) {
                el.textContent = translation;
              }
            }
          }
        }
      }
    });

    // Hỗ trợ thêm các attribute placeholder chuyên dụng: data-i18n-ph, data-placeholder-i18n, data-i18n-placeholder
    document
      .querySelectorAll(
        "[data-i18n-ph], [data-placeholder-i18n], [data-i18n-placeholder]",
      )
      .forEach((el) => {
        const key =
          el.getAttribute("data-i18n-ph") ||
          el.getAttribute("data-placeholder-i18n") ||
          el.getAttribute("data-i18n-placeholder");
        if (!key) return;
        const translation = window.t(key);
        if (
          translation !== undefined &&
          translation !== null &&
          translation !== ""
        ) {
          if (el.getAttribute("placeholder") !== translation) {
            el.setAttribute("placeholder", translation);
          }
        }
      });

    // Hỗ trợ data-i18n-title cho tooltip title
    document.querySelectorAll("[data-i18n-title]").forEach((el) => {
      const key = el.getAttribute("data-i18n-title");
      if (!key) return;
      const translation = window.t(key);
      if (
        translation !== undefined &&
        translation !== null &&
        translation !== ""
      ) {
        if (el.getAttribute("title") !== translation) {
          el.setAttribute("title", translation);
        }
      }
    });
  }

  // Cập nhật nút Dropdown
  function updateLanguageDropdownUI() {
    const langConfig = DICT[currentLang] || DICT["vi"];
    document.querySelectorAll(".lang-btn-current").forEach((el) => {
      const codeSpan = el.querySelector(".lang-code");
      const flagSpan = el.querySelector(".lang-flag");
      if (codeSpan) {
        codeSpan.textContent = langConfig.short;
      }
      if (flagSpan) {
        flagSpan.textContent = langConfig.flag;
      }
      if (!codeSpan && !flagSpan) {
        el.innerHTML = `<span class="lang-flag">${langConfig.flag}</span> <span class="lang-code">${langConfig.short}</span> <span class="lang-arrow">▾</span>`;
      }
    });
    document.querySelectorAll(".lang-menu-item").forEach((item) => {
      const itemLang = item.getAttribute("data-lang");
      if (itemLang === currentLang) {
        item.classList.add("active");
      } else {
        item.classList.remove("active");
      }
    });
  }

  // Khởi tạo Language Switcher
  function initLanguageSwitcher() {
    updateLanguageDropdownUI();

    document.addEventListener("click", function (e) {
      const toggle = e.target.closest(".lang-btn-current");
      const dropdown = e.target.closest(".lang-switcher-dropdown");

      if (toggle) {
        e.preventDefault();
        e.stopPropagation();
        const parent = toggle.closest(".lang-switcher-dropdown");
        if (parent) {
          parent.classList.toggle("open");
        }
      } else if (!dropdown) {
        document
          .querySelectorAll(".lang-switcher-dropdown.open")
          .forEach((dd) => {
            dd.classList.remove("open");
          });
      }

      const item = e.target.closest(".lang-menu-item");
      if (item) {
        e.preventDefault();
        const lang = item.getAttribute("data-lang");
        if (lang) {
          window.setAppLanguage(lang, true);
        }
      }
    });
  }

  // Tự động lắng nghe thay đổi DOM (MutationObserver) để dịch nội dung mới được thêm
  function observeDOMChanges() {
    if (!window.MutationObserver) return;
    if (observerInstance) {
      observerInstance.disconnect();
    }

    observerInstance = new MutationObserver((mutations) => {
      if (isTranslating) return;

      let hasNewNodes = false;
      for (const m of mutations) {
        // Chỉ kích hoạt khi có node mới được thêm thực sự từ Ajax/render động
        if (m.addedNodes && m.addedNodes.length > 0) {
          for (const node of m.addedNodes) {
            // Bỏ qua các node do chính i18n tạo ra hoặc text node đơn thuần
            if (
              node.nodeType === Node.ELEMENT_NODE &&
              !node.classList?.contains("lang-switcher-dropdown")
            ) {
              hasNewNodes = true;
              break;
            }
          }
        }
        if (hasNewNodes) break;
      }

      if (hasNewNodes) {
        if (observerDebounceTimer) clearTimeout(observerDebounceTimer);
        observerDebounceTimer = setTimeout(() => {
          if (!isTranslating) {
            deepTranslateDOM();
          }
        }, 150);
      }
    });

    observerInstance.observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      initLanguageSwitcher();
      deepTranslateDOM();
      observeDOMChanges();
    });
  } else {
    initLanguageSwitcher();
    deepTranslateDOM();
    observeDOMChanges();
  }

  // Dự phòng an toàn nếu toast.js chưa nạp kịp
  if (typeof window.Toast === "undefined") {
    window.Toast = {
      show(msg, type = "info", duration = 3500) {
        console.log(`[Toast ${type}]: ${msg}`);
      },
      success(msg, d) {
        return this.show(msg, "success", d);
      },
      error(msg, d) {
        return this.show(msg, "error", d);
      },
      warning(msg, d) {
        return this.show(msg, "warning", d);
      },
      info(msg, d) {
        return this.show(msg, "info", d);
      },
      flash(msg, type = "success") {
        try {
          sessionStorage.setItem(
            "bobin_toast_flash",
            JSON.stringify({ message: msg, type: type, timestamp: Date.now() }),
          );
        } catch (e) { }
      },
    };
    window.showToast = window.Toast.show;
  }
})(window, document);
