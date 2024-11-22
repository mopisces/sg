安装nssm服务
1.解压对应操作系统的的nssm压缩包(只支持win7/win8/win10)
2.cmd定位至nssm.exe所在目录(选择对应的操作系统位数)
3.在cmd中输入nssm install {服务名称} ,即注册服务的名称 例如 nssm install sg_line_one
4.在弹出框的 Application path 中选择需要安装的服务(以.bat结尾的文件),点击Install serveice
注:需要修改.bat 文件中文件路径
5.安装完成后在系统的服务中可以找到相应的注册服务来开启或者关闭
卸载nssm服务
1.在系统服务中关闭需要删除的服务
2.重复安装步骤1,2
3.在cmd中输入nssm remove {服务名称}, 即删除服务的名称, 例如 nssm remove sg_line_one
4.弹出框中点击确认即可删除
nssm 其他命令:
nssm start {服务名称}
nssm stop {服务名称}
nssm restart {服务名称}
