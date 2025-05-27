// Sample questions data structure
const examQuestions = {
  riazi: [
    {
      question: "اگر f(x) = x² + 2x + 1 باشد، حاصل f(2) کدام است؟",
      options: ["3", "5", "7", "9"],
      correctAnswer: 3,
    },
    {
      question: "مجموعه جواب نامعادله x² - 4 > 0 کدام است؟",
      options: ["(-∞, -2) ∪ (2, +∞)", "(-2, 2)", "[-2, 2]", "(-∞, 2)"],
      correctAnswer: 0,
    },
    // Add more questions here
  ],
  tajrobi: [
    {
      question: "کدام یک از موارد زیر جزء اندامک‌های غشادار سلول است؟",
      options: ["ریبوزوم", "میتوکندری", "سانتریول", "تاژک"],
      correctAnswer: 1,
    },
    // Add more questions here
  ],
  ensani: [
    {
      question: "کدام گزینه از آثار ناصر خسرو نیست؟",
      options: ["سفرنامه", "قابوسنامه", "جامع الحکمتین", "خوان اخوان"],
      correctAnswer: 1,
    },
    // Add more questions here
  ],
  honar: [
    {
      question: "کدام یک از هنرمندان زیر از نقاشان سبک امپرسیونیسم است؟",
      options: ["ونسان ون گوگ", "کلود مونه", "پابلو پیکاسو", "سالوادور دالی"],
      correctAnswer: 1,
    },
    // Add more questions here
  ],
};

let currentExam = null;
let currentQuestionIndex = 0;
let userAnswers = [];
let timeLeft = 0;
let timerInterval;

// تعریف ساختار داده‌ها
let subjects = {};
let currentUser = null;

// متغیرهای سراسری
let currentSubject = "";
let currentModule = null;
let currentPage = 0;
let answers = {};

// بارگذاری اطلاعات ذخیره شده
function loadSavedData() {
  try {
    // بارگذاری اطلاعات کاربر
    currentUser =
      JSON.parse(localStorage.getItem("currentUser")) ||
      JSON.parse(sessionStorage.getItem("currentUser"));

    if (!currentUser) {
      window.location.href = "login.html";
      return;
    }

    // نمایش نام کاربر
    const userFullnameElement = document.getElementById("userFullname");
    if (userFullnameElement) {
      userFullnameElement.textContent = currentUser.fullname;
    }

    // بارگذاری اطلاعات کتاب‌ها
    const users = JSON.parse(localStorage.getItem("users")) || [];
    const user = users.find((u) => u.id === currentUser.id);

    if (user && user.subjects && Object.keys(user.subjects).length > 0) {
      subjects = user.subjects;
    } else {
      // اطلاعات پیش‌فرض برای کاربر جدید
      subjects = {
        daneshFanni: {
          name: "دانش فنی",
          grade: "پایه دوازدهم",
          modules: [
            { id: 1, name: "پودمان 1", questions: 20 },
            { id: 2, name: "پودمان 2", questions: 20 },
            { id: 3, name: "پودمان 3", questions: 20 },
            { id: 4, name: "پودمان 4", questions: 20 },
            { id: 5, name: "پودمان 5", questions: 20 },
          ],
        },
        takhassosi: {
          name: "دروس تخصصی",
          grade: "پایه دوازدهم",
          modules: [
            { id: 1, name: "پودمان 1", questions: 30 },
            { id: 2, name: "پودمان 2", questions: 30 },
            { id: 3, name: "پودمان 3", questions: 30 },
            { id: 4, name: "پودمان 4", questions: 30 },
            { id: 5, name: "پودمان 5", questions: 30 },
          ],
        },
      };
      saveSubjects();
    }

    // بارگذاری پاسخ‌ها
    const savedAnswers = localStorage.getItem(`answers_${currentUser.id}`);
    if (savedAnswers) {
      answers = JSON.parse(savedAnswers);
    }

    // بروزرسانی رابط کاربری
    updateUI();
  } catch (error) {
    console.error("Error loading data:", error);
  }
}

// بروزرسانی رابط کاربری
function updateUI() {
  try {
    updateSubjectList();
    updateSettingsSubjectList();
  } catch (error) {
    console.error("Error updating UI:", error);
  }
}

// مخفی کردن همه بخش‌ها
function hideAllSections() {
  const sections = ["moduleSection", "answerSection"];
  sections.forEach((id) => {
    const element = document.getElementById(id);
    if (element) {
      element.style.display = "none";
    }
  });
}

// نمایش بخش اصلی
function showMainSection() {
  hideAllSections();
  const subjectSection = document.getElementById("subjectSection");
  if (subjectSection) {
    subjectSection.style.display = "block";
  }
  updateUI();
}

// بروزرسانی لیست کتاب‌ها
function updateSubjectList() {
  const subjectList = document.getElementById("subjectList");
  if (!subjectList) return;

  subjectList.innerHTML = Object.entries(subjects)
    .map(
      ([id, subject]) => `
        <div class="position-relative">
            <button class="btn btn-lg btn-outline-primary book-btn w-100" onclick="showModules('${id}')">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="book-name">${subject.name}</span>
                    <div>
                        <small class="text-muted">${subject.grade}</small>
                        <button class="btn btn-sm btn-outline-secondary ms-2" 
                                onclick="event.stopPropagation(); showModuleEditModal('${id}')">
                            <i class="fas fa-cog"></i>
                        </button>
                    </div>
                </div>
            </button>
        </div>
    `
    )
    .join("");
}

// بروزرسانی لیست کتاب‌ها در تنظیمات
function updateSettingsSubjectList() {
  const settingsList = document.getElementById("settingsSubjectList");
  if (!settingsList) return;

  settingsList.innerHTML = Object.entries(subjects)
    .map(
      ([id, subject]) => `
        <tr>
            <td>${subject.name}</td>
            <td>${subject.grade}</td>
            <td>${subject.modules[0].questions}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="editSubject('${id}')">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger ms-2" onclick="deleteSubject('${id}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `
    )
    .join("");
}

// راه‌اندازی برنامه
function initializeApp() {
  try {
    loadSavedData();
    showMainSection();
  } catch (error) {
    console.error("Error initializing app:", error);
  }
}

// بارگذاری اطلاعات در هنگام بارگذاری صفحه
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeApp);
} else {
  initializeApp();
}

// افزودن کتاب جدید
function addNewSubject() {
  const name = document.getElementById("subjectName").value;
  const grade = document.getElementById("subjectGrade").value;
  const questionsCount = parseInt(
    document.getElementById("questionsPerModule").value
  );

  if (!name || !grade || !questionsCount) {
    alert("لطفاً همه فیلدها را پر کنید");
    return;
  }

  const id = name.toLowerCase().replace(/\s+/g, "_");

  if (subjects[id]) {
    alert("این کتاب قبلاً اضافه شده است");
    return;
  }

  subjects[id] = {
    name: name,
    grade: grade,
    modules: [
      { id: 1, name: "پودمان 1", questions: questionsCount },
      { id: 2, name: "پودمان 2", questions: questionsCount },
      { id: 3, name: "پودمان 3", questions: questionsCount },
      { id: 4, name: "پودمان 4", questions: questionsCount },
      { id: 5, name: "پودمان 5", questions: questionsCount },
    ],
  };

  saveSubjects();
  updateSubjectList();
  updateSettingsSubjectList();

  const modal = bootstrap.Modal.getInstance(
    document.getElementById("addSubjectModal")
  );
  modal.hide();
  document.getElementById("addSubjectForm").reset();
}

// ویرایش کتاب
function editSubject(id) {
  const subject = subjects[id];
  document.getElementById("editSubjectId").value = id;
  document.getElementById("editSubjectName").value = subject.name;
  document.getElementById("editSubjectGrade").value = subject.grade;
  document.getElementById("editQuestionsPerModule").value =
    subject.modules[0].questions;

  const settingsModal = bootstrap.Modal.getInstance(
    document.getElementById("settingsModal")
  );
  settingsModal.hide();

  const editModal = new bootstrap.Modal(
    document.getElementById("editSubjectModal")
  );
  editModal.show();
}

// بروزرسانی کتاب
function updateSubject() {
  const id = document.getElementById("editSubjectId").value;
  const name = document.getElementById("editSubjectName").value;
  const grade = document.getElementById("editSubjectGrade").value;
  const questionsCount = parseInt(
    document.getElementById("editQuestionsPerModule").value
  );

  if (!name || !grade || !questionsCount) {
    alert("لطفاً همه فیلدها را پر کنید");
    return;
  }

  subjects[id] = {
    name: name,
    grade: grade,
    modules: subjects[id].modules.map((module) => ({
      ...module,
      questions: questionsCount,
    })),
  };

  saveSubjects();
  updateSubjectList();
  updateSettingsSubjectList();

  const editModal = bootstrap.Modal.getInstance(
    document.getElementById("editSubjectModal")
  );
  editModal.hide();

  const settingsModal = new bootstrap.Modal(
    document.getElementById("settingsModal")
  );
  settingsModal.show();
}

// حذف کتاب
function deleteSubject(id) {
  if (confirm(`آیا از حذف کتاب "${subjects[id].name}" اطمینان دارید؟`)) {
    delete subjects[id];
    saveSubjects();
    updateSubjectList();
    updateSettingsSubjectList();
  }
}

// ذخیره تغییرات در localStorage
function saveSubjects() {
  const users = JSON.parse(localStorage.getItem("users")) || [];
  const userIndex = users.findIndex((u) => u.id === currentUser.id);

  if (userIndex !== -1) {
    users[userIndex].subjects = subjects;
    localStorage.setItem("users", JSON.stringify(users));
  }
}

// نمایش پودمان‌های یک درس
function showModules(subjectId) {
  currentSubject = subjectId;
  const subject = subjects[subjectId];

  // نمایش نام درس
  document.getElementById("selectedSubject").textContent = subject.name;

  // نمایش لیست پودمان‌ها
  const moduleList = document.getElementById("moduleList");
  moduleList.innerHTML = subject.modules
    .map(
      (module) => `
    <button class="btn btn-outline-primary" onclick="showQuestions(${module.id})">
      ${module.name}
    </button>
  `
    )
    .join("");

  // تغییر نمایش بخش‌ها
  document.getElementById("subjectSection").style.display = "none";
  document.getElementById("moduleSection").style.display = "block";
}

// نمایش سوالات یک پودمان
function showQuestions(moduleId) {
  currentModule = subjects[currentSubject].modules.find(
    (m) => m.id === moduleId
  );
  currentPage = 0;

  // نمایش نام پودمان
  document.getElementById(
    "selectedModule"
  ).textContent = `${subjects[currentSubject].name} - ${currentModule.name}`;

  // نمایش سوالات
  showCurrentPage();

  // تغییر نمایش بخش‌ها
  document.getElementById("moduleSection").style.display = "none";
  document.getElementById("answerSection").style.display = "block";
}

// نمایش صفحه فعلی سوالات
function showCurrentPage() {
  const container = document.getElementById("questionContainer");
  const startQ = currentPage * 10;
  const endQ = Math.min(startQ + 10, currentModule.questions);

  container.innerHTML = "";

  for (let i = startQ; i < endQ; i++) {
    const questionNumber = i + 1;
    const answerId = `${currentSubject}_${currentModule.id}_${questionNumber}`;
    const currentAnswer = answers[answerId] || "";

    container.innerHTML += `
      <div class="col">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="text-center mb-3">سوال ${questionNumber}</h5>
            <div class="btn-group-vertical w-100">
              ${[1, 2, 3, 4]
                .map(
                  (option) => `
                <input type="radio" class="btn-check" 
                  name="q${questionNumber}" 
                  id="q${questionNumber}_${option}"
                  ${currentAnswer === option ? "checked" : ""}
                  onchange="saveAnswer('${answerId}', ${option})">
                <label class="btn btn-outline-primary" 
                  for="q${questionNumber}_${option}">
                  ${option}
                </label>
              `
                )
                .join("")}
            </div>
          </div>
        </div>
      </div>
    `;
  }
}

// ذخیره پاسخ
function saveAnswer(id, value) {
  answers[id] = value;
  localStorage.setItem(`answers_${currentUser.id}`, JSON.stringify(answers));
}

// صفحه قبلی
function prevPage() {
  if (currentPage > 0) {
    currentPage--;
    showCurrentPage();
  }
}

// صفحه بعدی
function nextPage() {
  if ((currentPage + 1) * 10 < currentModule.questions) {
    currentPage++;
    showCurrentPage();
  }
}

// بازگشت به صفحه درس‌ها
function backToSubjects() {
  document.getElementById("moduleSection").style.display = "none";
  document.getElementById("subjectSection").style.display = "block";
  currentSubject = "";
  currentModule = null;
}

// بازگشت به صفحه پودمان‌ها
function backToModules() {
  document.getElementById("answerSection").style.display = "none";
  document.getElementById("moduleSection").style.display = "block";
  currentModule = null;
  currentPage = 0;
}

// Subject selection
function selectSubject(subjectId) {
  currentSubject = subjectId;

  // Update UI
  document.getElementById("subject-section").style.display = "none";
  document.getElementById("module-section").style.display = "block";
  document.getElementById("selected-subject").textContent =
    subjects[subjectId].name;

  // Generate module buttons
  const moduleList = document.getElementById("module-list");
  moduleList.innerHTML = subjects[subjectId].modules
    .map(
      (module) => `
    <button class="list-group-item list-group-item-action" onclick="selectModule('${module.id}')">
      ${module.name}
    </button>
  `
    )
    .join("");
}

// Module selection
function selectModule(moduleId) {
  currentModule = moduleId;
  currentPage = 0;

  // Update UI
  document.getElementById("module-section").style.display = "none";
  document.getElementById("answer-section").style.display = "block";

  const selectedModule = subjects[currentSubject].modules.find(
    (m) => m.id === moduleId
  );
  document.getElementById(
    "selected-module"
  ).textContent = `${subjects[currentSubject].name} - ${selectedModule.name}`;

  showCurrentPage();
}

// Show current set of questions
function showQuestionSet() {
  const answerSheet = document.getElementById("answer-sheet");
  const selectedModule = subjects[currentSubject].modules.find(
    (m) => m.id === currentModule
  );
  const questionCount = Math.min(10, selectedModule.questions - currentPage);

  answerSheet.innerHTML = "";

  for (let i = 0; i < questionCount; i++) {
    const questionNumber = currentPage + i + 1;
    const answerId = `${currentSubject}_${currentModule.id}_${questionNumber}`;
    const selectedAnswer = answers[answerId] || "";

    const questionHtml = `
      <div class="col">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title text-center">سوال ${questionNumber}</h5>
            <div class="btn-group-vertical w-100" role="group">
              ${[1, 2, 3, 4]
                .map(
                  (option) => `
                <input type="radio" class="btn-check" name="q${questionNumber}" 
                  id="q${questionNumber}_${option}" 
                  ${selectedAnswer === option.toString() ? "checked" : ""}
                  onchange="saveAnswer('${answerId}', ${option})">
                <label class="btn btn-outline-primary" for="q${questionNumber}_${option}">
                  ${option}
                </label>
              `
                )
                .join("")}
            </div>
          </div>
        </div>
      </div>
    `;
    answerSheet.innerHTML += questionHtml;
  }
}

// Navigation functions
function nextQuestionSet() {
  const selectedModule = subjects[currentSubject].modules.find(
    (m) => m.id === currentModule
  );
  if (currentPage + 10 < selectedModule.questions) {
    currentPage += 10;
    showQuestionSet();
  }
}

function previousQuestionSet() {
  if (currentPage >= 10) {
    currentPage -= 10;
    showQuestionSet();
  }
}

// Answer key functions
function toggleAnswerKey() {
  const answerSection = document.getElementById("answer-section");
  const answerKeySection = document.getElementById("answer-key-section");

  if (answerSection.style.display === "none") {
    answerSection.style.display = "block";
    answerKeySection.style.display = "none";
  } else {
    answerSection.style.display = "none";
    answerKeySection.style.display = "block";
    showAnswerKey();
  }
}

function showAnswerKey() {
  const answerKey = document.getElementById("answer-key");
  const selectedModule = subjects[currentSubject].modules.find(
    (m) => m.id === currentModule
  );

  answerKey.innerHTML = "";

  for (let i = 1; i <= selectedModule.questions; i++) {
    const answerId = `${currentSubject}_${currentModule.id}_${i}`;
    const userAnswer = answers[answerId] || "-";

    answerKey.innerHTML += `
      <div class="col">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title text-center">سوال ${i}</h5>
            <p class="text-center mb-0">پاسخ شما: ${userAnswer}</p>
          </div>
        </div>
      </div>
    `;
  }
}

function startExam(examType) {
  currentExam = examType;
  currentQuestionIndex = 0;
  userAnswers = new Array(examQuestions[examType].length).fill(null);
  timeLeft = examQuestions[examType].length * 120; // 2 minutes per question

  document.getElementById("start-section").style.display = "none";
  document.getElementById("exam-section").style.display = "block";
  document.getElementById("exam-title").textContent = `آزمون ${getExamTitle(
    examType
  )}`;

  startTimer();
  showQuestion();
}

function getExamTitle(examType) {
  const titles = {
    riazi: "ریاضی",
    tajrobi: "تجربی",
    ensani: "انسانی",
    honar: "هنر",
  };
  return titles[examType];
}

function startTimer() {
  updateTimerDisplay();
  timerInterval = setInterval(() => {
    timeLeft--;
    updateTimerDisplay();
    if (timeLeft <= 0) {
      finishExam();
    }
  }, 1000);
}

function updateTimerDisplay() {
  const minutes = Math.floor(timeLeft / 60);
  const seconds = timeLeft % 60;
  document.getElementById("timer").textContent = `${minutes
    .toString()
    .padStart(2, "0")}:${seconds.toString().padStart(2, "0")}`;
}

function showQuestion() {
  const question = examQuestions[currentExam][currentQuestionIndex];
  const container = document.getElementById("question-container");

  container.innerHTML = `
    <div class="question-item">
      <h4 class="mb-4">سوال ${currentQuestionIndex + 1} از ${
    examQuestions[currentExam].length
  }</h4>
      <p class="h5 mb-4">${question.question}</p>
      <div class="options-container">
        ${question.options
          .map(
            (option, index) => `
            <div class="option-item ${
              userAnswers[currentQuestionIndex] === index ? "selected" : ""
            }"
               onclick="selectAnswer(${index})">
              ${["الف", "ب", "ج", "د"][index]}) ${option}
            </div>
          `
          )
          .join("")}
      </div>
    </div>
  `;
}

function selectAnswer(answerIndex) {
  userAnswers[currentQuestionIndex] = answerIndex;
  showQuestion();
}

function previousQuestion() {
  if (currentQuestionIndex > 0) {
    currentQuestionIndex--;
    showQuestion();
  }
}

function nextQuestion() {
  if (currentQuestionIndex < examQuestions[currentExam].length - 1) {
    currentQuestionIndex++;
    showQuestion();
  } else {
    finishExam();
  }
}

function finishExam() {
  clearInterval(timerInterval);

  const correctAnswers = examQuestions[currentExam].reduce(
    (count, question, index) => {
      return count + (userAnswers[index] === question.correctAnswer ? 1 : 0);
    },
    0
  );

  const percentage = Math.round(
    (correctAnswers / examQuestions[currentExam].length) * 100
  );

  document.getElementById("exam-section").style.display = "none";
  document.getElementById("result-section").style.display = "block";

  document.getElementById("result-details").innerHTML = `
    <div class="result-card">
      <h3 class="mb-4">نتیجه نهایی</h3>
      <p class="h5">تعداد پاسخ‌های صحیح: ${correctAnswers} از ${
    examQuestions[currentExam].length
  }</p>
      <p class="h5">درصد موفقیت: ${percentage}%</p>
    </div>
    <div class="mt-4">
      <h4>بررسی پاسخ‌ها:</h4>
      ${examQuestions[currentExam]
        .map(
          (question, index) => `
          <div class="result-card">
            <p class="h6">${index + 1}. ${question.question}</p>
            <p>پاسخ شما: ${
              userAnswers[index] !== null
                ? question.options[userAnswers[index]]
                : "بدون پاسخ"
            }</p>
            <p class="${
              userAnswers[index] === question.correctAnswer
                ? "correct-answer"
                : "wrong-answer"
            }">
              پاسخ صحیح: ${question.options[question.correctAnswer]}
            </p>
          </div>
        `
        )
        .join("")}
    </div>
  `;
}

function restartExam() {
  document.getElementById("result-section").style.display = "none";
  document.getElementById("start-section").style.display = "block";
  currentExam = null;
  currentQuestionIndex = 0;
  userAnswers = [];
  timeLeft = 0;
}

// Initialize answers when page loads
document.addEventListener("DOMContentLoaded", initAnswers);

// نمایش مودال ویرایش پودمان‌ها
function showModuleEditModal(subjectId) {
  const subject = subjects[subjectId];
  if (!subject) return;

  const moduleEditForm = document.getElementById("moduleEditForm");
  moduleEditForm.innerHTML = `
        <input type="hidden" id="editingSubjectId" value="${subjectId}">
        <div class="row mb-3">
            <div class="col">
                <h6 class="mb-3">کتاب: ${subject.name}</h6>
            </div>
        </div>
        ${subject.modules
          .map(
            (module, index) => `
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">نام پودمان ${index + 1}</label>
                    <input type="text" class="form-control module-name" 
                           value="${module.name}" 
                           data-module-id="${module.id}"
                           placeholder="نام پودمان">
                </div>
                <div class="col-md-6">
                    <label class="form-label">تعداد سوالات</label>
                    <input type="number" class="form-control module-questions" 
                           value="${module.questions}" 
                           data-module-id="${module.id}"
                           min="1" max="100">
                </div>
            </div>
        `
          )
          .join("")}
    `;

  const modal = new bootstrap.Modal(
    document.getElementById("editModulesModal")
  );
  modal.show();
}

// ذخیره تغییرات پودمان‌ها
function saveModuleChanges() {
  const subjectId = document.getElementById("editingSubjectId").value;
  const moduleRows = document.querySelectorAll(
    "#moduleEditForm .row:not(:first-child)"
  );
  const modules = [];

  moduleRows.forEach((row) => {
    const nameInput = row.querySelector(".module-name");
    const questionsInput = row.querySelector(".module-questions");
    const defaultQuestions =
      questionsInput.value ||
      moduleRows[0].querySelector(".module-questions").value;

    modules.push({
      id: nameInput.getAttribute("data-module-id"),
      name: nameInput.value.trim() || `پودمان ${modules.length + 1}`,
      questions: parseInt(questionsInput.value) || parseInt(defaultQuestions),
    });
  });

  // نمایش لودر
  document.getElementById("loaderOverlay").classList.add("active");

  // ارسال درخواست به سرور
  fetch("save_module_changes.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      subject_id: subjectId,
      modules: modules,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // بستن مودال و بروزرسانی صفحه
        const modal = bootstrap.Modal.getInstance(
          document.getElementById("editModulesModal")
        );
        modal.hide();

        // نمایش پیام موفقیت‌آمیز
        showNotification("تغییرات با موفقیت ذخیره شد", "success");

        // بروزرسانی صفحه بعد از 1.5 ثانیه
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification(data.message || "خطا در ذخیره تغییرات", "error");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      showNotification("خطا در ارتباط با سرور", "error");
    })
    .finally(() => {
      // مخفی کردن لودر
      document.getElementById("loaderOverlay").classList.remove("active");
    });
}
