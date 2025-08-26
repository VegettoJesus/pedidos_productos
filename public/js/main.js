const menuItemsDropDown = document.querySelectorAll('.menu-item-dropdown')
const menusItemsStatic = document.querySelectorAll('.menu-item-static')
const sidebar = document.getElementById('sidebar');
const menuBtn = document.getElementById('menu-btn');
const sidebarBtn = document.getElementById('sidebar-btn')
const darkModeBtn = document.getElementById('dark-mode-btn');

darkModeBtn.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode')
});

sidebarBtn.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-hidden')
});

menuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('minimize');
});

menuItemsDropDown.forEach((menuItem)=>{
    menuItem.addEventListener('click',()=>{
        console.log(menuItemsDropDown)
        const subMenu = menuItem.querySelector('.sub-menu');
        const isActive = menuItem.classList.toggle('sub-menu-toggle');
        if(subMenu){
            if(isActive){
                subMenu.style.height = `${subMenu.scrollHeight + 6}px`
                subMenu.style.padding = '0.2rem 0'
                subMenu.style.width = 'max-content'
            }else{
                subMenu.style.height = '0';
                subMenu.style.padding = '0';
                subMenu.style.width = 'max-content'
            }
        }
        menuItemsDropDown.forEach((item)=>{
            if(item !== menuItem){
                const otherSubmenu = item.querySelector('.sub-menu');
                if(otherSubmenu){
                    item.classList.remove('sub-menu-toggle');
                    otherSubmenu.style.height = '0';
                    otherSubmenu.style.padding = '0';
                }
            }
        })
    });
})
menusItemsStatic.forEach((menuItem) =>{
    menuItem.addEventListener('mouseenter', () =>{

        if(!sidebar.classList.contains('minimize')) return;

        menuItemsDropDown.forEach((item)=>{
            const otherSubmenu = item.querySelector('.sub-menu');
            if(otherSubmenu){
                item.classList.remove('sub-menu-toggle');
                otherSubmenu.style.height = '0';
                otherSubmenu.style.padding = '0';
            }
        })
    })
})
function checkWindowsSize(){
    sidebar.classList.remove('minimize')
}
checkWindowsSize();
window.addEventListener('resize',checkWindowsSize);