<native:bottom-bar>
    <native:row class="w-full p-2 gap-2 bg-theme-surface border border-theme-outline-variant">
        <native:button label="الرئيسية" icon="dashboard" size="sm" :variant="$activeTab === 'home' ? 'primary' : 'ghost'" @press="showDashboard" class="flex-1" />
        <native:button label="الجهات" icon="people" size="sm" :variant="$activeTab === 'creditors' ? 'primary' : 'ghost'" @press="showCreditors" class="flex-1" />
        <native:button label="الإعدادات" icon="settings" size="sm" variant="ghost" @navigate="'/settings'" class="flex-1" />
        <native:button label="المواعيد" icon="event" size="sm" variant="ghost" @navigate="'/payment-promises'" class="flex-1" />
    </native:row>
</native:bottom-bar>
