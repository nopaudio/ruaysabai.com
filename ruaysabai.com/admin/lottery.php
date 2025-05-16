<div id="tab-rates" class="tab-content">
                <form method="POST" action="" id="update-rates-form">
                    <input type="hidden" id="lottery_id_rates" name="lottery_id">
                    
                    <div class="form-group">
                        <label for="lottery_date_rates">วันที่งวด:</label>
                        <div class="form-control-wrap">
                            <input type="text" id="lottery_date_rates" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_prize_rate_edit">อัตราจ่ายรางวัลที่ 1:</label>
                        <div class="form-control-wrap">
                            <input type="number" id="first_prize_rate_edit" name="first_prize_rate" class="form-control" min="1" step="1" required>
                            <div class="form-text">บาทละกี่บาท</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="front_three_rate_edit">อัตราจ่ายเลขหน้า 3 ตัว:</label>
                        <div class="form-control-wrap">
                            <input type="number" id="front_three_rate_edit" name="front_three_rate" class="form-control" min="1" step="1" required>
                            <div class="form-text">บาทละกี่บาท</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="back_three_rate_edit">อัตราจ่ายเลขท้าย 3 ตัว:</label>
                        <div class="form-control-wrap">
                            <input type="number" id="back_three_rate_edit" name="back_three_rate" class="form-control" min="1" step="1" required>
                            <div class="form-text">บาทละกี่บาท</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="back_two_rate_edit">อัตราจ่ายเลขท้าย 2 ตัว:</label>
                        <div class="form-control-wrap">
                            <input type="number" id="back_two_rate_edit" name="back_two_rate" class="form-control" min="1" step="1" required>
                            <div class="form-text">บาทละกี่บาท</div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_rates" class="btn btn-accent">บันทึกอัตราการจ่ายเงิน</button>
                        <button type="button" class="btn close-modal">ยกเลิก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal
            const modal = document.getElementById('editLotteryModal');
            const closeButtons = document.querySelectorAll('.close, .close-modal');
            const editButtons = document.querySelectorAll('.edit-lottery');
            
            // ฟอร์ม
            const resultsForm = document.getElementById('set-results-form');
            const ratesForm = document.getElementById('update-rates-form');
            
            // แท็บ
            const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');
            
            // เปิด Modal เมื่อคลิกปุ่มแก้ไข
            editButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const id = this.getAttribute('data-id');
                    const date = this.getAttribute('data-date');
                    const status = this.getAttribute('data-status');
                    const firstPrize = this.getAttribute('data-first-prize');
                    const frontThree = this.getAttribute('data-front-three');
                    const backThree = this.getAttribute('data-back-three');
                    const backTwo = this.getAttribute('data-back-two');
                    const rateFirstPrize = this.getAttribute('data-rate-first-prize');
                    const rateFrontThree = this.getAttribute('data-rate-front-three');
                    const rateBackThree = this.getAttribute('data-rate-back-three');
                    const rateBackTwo = this.getAttribute('data-rate-back-two');
                    
                    // ตั้งค่าข้อมูลใน Modal
                    document.getElementById('lottery_id_results').value = id;
                    document.getElementById('lottery_id_rates').value = id;
                    
                    document.getElementById('lottery_date_display').value = formatDate(date);
                    document.getElementById('lottery_date_rates').value = formatDate(date);
                    
                    // ตั้งค่าผลรางวัล
                    document.getElementById('first_prize').value = firstPrize || '';
                    document.getElementById('front_three').value = frontThree || '';
                    document.getElementById('back_three').value = backThree || '';
                    document.getElementById('back_two').value = backTwo || '';
                    
                    // ตั้งค่าอัตราการจ่ายเงิน
                    document.getElementById('first_prize_rate_edit').value = rateFirstPrize || 900;
                    document.getElementById('front_three_rate_edit').value = rateFrontThree || 500;
                    document.getElementById('back_three_rate_edit').value = rateBackThree || 500;
                    document.getElementById('back_two_rate_edit').value = rateBackTwo || 90;
                    
                    // เลือกแท็บที่เหมาะสม
                    if (status === 'completed') {
                        // ถ้าออกผลแล้ว เลือกแท็บอัตราการจ่ายเงิน
                        selectTab('tab-rates');
                    } else {
                        // ถ้ายังไม่ออกผล เลือกแท็บออกผลรางวัล
                        selectTab('tab-results');
                    }
                    
                    // เปิด Modal
                    modal.style.display = 'block';
                });
            });
            
            // ปิด Modal
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            });
            
            // คลิกนอก Modal เพื่อปิด
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // การทำงานของแท็บ
            tabLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const tabId = this.getAttribute('data-tab');
                    selectTab(tabId);
                });
            });
            
            // ฟังก์ชันสำหรับเลือกแท็บ
            function selectTab(tabId) {
                // ลบ class active จากทุกแท็บ
                tabLinks.forEach(link => link.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // เพิ่ม class active ให้กับแท็บที่เลือก
                document.querySelector(`.tab-link[data-tab="${tabId}"]`).classList.add('active');
                document.getElementById(tabId).classList.add('active');
            }
            
            // ฟังก์ชันสำหรับจัดรูปแบบวันที่
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' });
            }
        });
    </script>
</body>
</html>