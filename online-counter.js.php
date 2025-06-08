// online-counter.js - สคริปต์เสริมสำหรับ Online Count
// สามารถเพิ่มไฟล์นี้ใน head ของ index.php ถ้าต้องการระบบที่ซับซ้อนกว่า

class OnlineCounterManager {
    constructor() {
        this.baseCount = 127;
        this.currentCount = this.baseCount;
        this.isActive = true;
        this.updateInterval = null;
        this.lastUpdate = 0;
        this.countDisplay = document.getElementById('onlineCountDisplay');
        this.indicator = document.querySelector('.online-indicator');
        
        this.init();
    }
    
    init() {
        // เริ่มต้นการนับ
        this.currentCount = this.generateRealisticCount();
        this.updateDisplay(this.currentCount);
        
        // ตั้งค่าการอัปเดตแบบ interval
        this.startPeriodicUpdates();
        
        // ฟังการเปลี่ยนแปลง visibility ของหน้า
        this.setupVisibilityListener();
        
        // จำลองการเคลื่อนไหวเล็กน้อย
        this.startMicroAnimations();
    }
    
    generateRealisticCount() {
        const now = Date.now();
        const timeFactor = Math.floor(now / 300000); // เปลี่ยนทุก 5 นาที
        
        // สร้างรูปแบบที่สมจริงตามเวลา
        const hourOfDay = new Date().getHours();
        let timeMultiplier = 1;
        
        // Peak hours (9-11 AM และ 7-9 PM)
        if ((hourOfDay >= 9 && hourOfDay <= 11) || (hourOfDay >= 19 && hourOfDay <= 21)) {
            timeMultiplier = 1.3;
        }
        // Low hours (1-6 AM)
        else if (hourOfDay >= 1 && hourOfDay <= 6) {
            timeMultiplier = 0.7;
        }
        
        const baseVariance = (timeFactor % 37) + Math.floor(Math.random() * 40) - 15;
        const timeAdjustedCount = Math.floor(this.baseCount * timeMultiplier);
        
        return Math.max(50, timeAdjustedCount + baseVariance);
    }
    
    updateDisplay(count, animated = true) {
        if (!this.countDisplay) return;
        
        if (animated && this.currentCount !== count) {
            this.animateCountChange(this.currentCount, count);
        } else {
            this.countDisplay.textContent = count;
        }
        
        this.currentCount = count;
    }
    
    animateCountChange(fromCount, toCount) {
        if (!this.indicator) return;
        
        // เพิ่ม animation class
        this.indicator.classList.add('updating');
        
        const duration = 1500; // 1.5 วินาที
        const startTime = Date.now();
        const difference = toCount - fromCount;
        
        const animate = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // ใช้ easing function สำหรับ animation ที่นุ่มนวล
            const easedProgress = this.easeOutCubic(progress);
            const currentValue = Math.round(fromCount + (difference * easedProgress));
            
            this.countDisplay.textContent = currentValue;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                // เสร็จสิ้น animation
                this.countDisplay.textContent = toCount;
                setTimeout(() => {
                    this.indicator.classList.remove('updating');
                }, 300);
            }
        };
        
        requestAnimationFrame(animate);
    }
    
    easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }
    
    startPeriodicUpdates() {
        // อัปเดตทุก 2-4 นาที
        const updateInterval = 120000 + Math.random() * 120000; // 2-4 นาที
        
        this.updateInterval = setInterval(() => {
            if (this.isActive) {
                const newCount = this.generateRealisticCount();
                this.updateDisplay(newCount, true);
            }
        }, updateInterval);
    }
    
    startMicroAnimations() {
        // การเปลี่ยนแปลงเล็กน้อยทุก 30-60 วินาที
        setInterval(() => {
            if (this.isActive && Math.random() < 0.3) { // 30% โอกาส
                const variance = Math.floor(Math.random() * 6) - 3; // -3 ถึง +3
                const newCount = Math.max(50, this.currentCount + variance);
                this.updateDisplay(newCount, true);
            }
        }, 30000 + Math.random() * 30000); // 30-60 วินาที
    }
    
    setupVisibilityListener() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.isActive = false;
            } else {
                this.isActive = true;
                // อัปเดตทันทีเมื่อกลับมาดูหน้า
                setTimeout(() => {
                    const newCount = this.generateRealisticCount();
                    this.updateDisplay(newCount, true);
                }, 1000);
            }
        });
    }
    
    // เมธอดสำหรับการควบคุมจากภายนอก
    forceUpdate() {
        const newCount = this.generateRealisticCount();
        this.updateDisplay(newCount, true);
    }
    
    pause() {
        this.isActive = false;
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }
    
    resume() {
        this.isActive = true;
        this.startPeriodicUpdates();
    }
    
    setBaseCount(newBase) {
        this.baseCount = newBase;
        this.forceUpdate();
    }
}

// Gallery Refresh Manager
class GalleryRefreshManager {
    constructor() {
        this.lastRefreshTime = 0;
        this.refreshInterval = 600000; // 10 นาที
        this.microRefreshInterval = 120000; // 2 นาที
        this.refreshIndicator = document.getElementById('galleryRefreshIndicator');
        
        this.init();
    }
    
    init() {
        this.startAutoRefresh();
        this.setupUserActivityDetection();
    }
    
    startAutoRefresh() {
        // Major refresh ทุก 10 นาที
        setInterval(() => {
            this.triggerRefresh(true);
        }, this.refreshInterval);
        
        // Micro refresh สุ่มทุก 2-3 นาที
        setInterval(() => {
            if (Math.random() < 0.4) { // 40% โอกาส
                this.triggerRefresh(false);
            }
        }, this.microRefreshInterval);
    }
    
    triggerRefresh(major = false) {
        const now = Date.now();
        if (now - this.lastRefreshTime < 60000) return; // ไม่ refresh บ่อยเกิน 1 นาทีต่อครั้ง
        
        this.lastRefreshTime = now;
        this.showRefreshIndicator();
        
        // เรียกฟังก์ชัน refresh จาก main script
        if (typeof refreshGalleryWithAnimation === 'function') {
            refreshGalleryWithAnimation();
        } else if (typeof loadGalleryItems === 'function') {
            loadGalleryItems(1, true);
        }
    }
    
    showRefreshIndicator() {
        if (!this.refreshIndicator) return;
        
        this.refreshIndicator.classList.add('show');
        setTimeout(() => {
            this.refreshIndicator.classList.remove('show');
        }, 2500);
    }
    
    setupUserActivityDetection() {
        let activityTimer = null;
        
        const resetActivityTimer = () => {
            clearTimeout(activityTimer);
            activityTimer = setTimeout(() => {
                // ผู้ใช้ไม่มี activity นาน 5 นาที = อาจจะ refresh
                if (Math.random() < 0.6) {
                    this.triggerRefresh(false);
                }
            }, 300000); // 5 นาที
        };
        
        // ตรวจจับ user activity
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetActivityTimer, { passive: true });
        });
        
        resetActivityTimer();
    }
}

// Auto-initialize เมื่อ DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // รอให้ main script โหลดเสร็จก่อน
    setTimeout(() => {
        try {
            const onlineCounter = new OnlineCounterManager();
            const galleryRefresh = new GalleryRefreshManager();
            
            // เก็บ reference ใน window object สำหรับการควบคุมจากภายนอก
            window.onlineCounter = onlineCounter;
            window.galleryRefresh = galleryRefresh;
            
            console.log('Online Counter และ Gallery Refresh System เริ่มทำงานแล้ว');
        } catch (error) {
            console.warn('ไม่สามารถเริ่มระบบ Auto-refresh ได้:', error);
        }
    }, 2000);
});

// Export สำหรับใช้ใน module system
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { OnlineCounterManager, GalleryRefreshManager };
}