# easytp

### 代码托管
| 名称   | 地址                                      |
| ------ | ----------------------------------------- |
| github |https://github.com/repertory/easytp.git |
| aliyun |https://code.aliyun.com/wangdong/easytp.git |

---

### 特别鸣谢

> 感谢以下朋友的捐赠 (按捐赠时间排列)

`王建峰` `朱闯` `杨瀚森` `任志强` `董亚明` `徐永宾` `郑宗岳` `翌玄` `李军辉` `建亮` 等

> 感谢以下网友代码上的贡献 (排名不分先后)

`XOFER` 等

---

### 常见问题
- [x] 安装环境有什么要求？

> **答**：要求PHP5.3以上，apache或者nginx都可以

- [x] 程序部署后直接跳到了404页面？

> **答**：默认需要配置伪静态才能正常访问，如果不会配置可以修改PHP全局的配置文件 `App/Common/Conf/config.php` 中的URL模式即可

- [x] 不会安装imagick扩展怎么办？

> **答**：从2.0版本开始已经不再强制开启imagick扩展了，但必须启用php-gd扩展

- [x] 安装密码是什么？

> **答**：安装密码为：`jeasytp.com`

- [x] 可以在SAE环境上运行吗？

> **答**： 两种方式部署：
> 1.SAE上新建应用选择导入代码即可直接运行（推荐使用）；
> 2.其他方式则必须先手动开启`storage`(命名为public) `memcache` `kvdb` `mysql`(共享型)
> 然后修改文件config.yaml，增加rewrite规则

```yaml
- rewrite: if(!is_dir() && !is_file() && path~"^(.*)$") goto "index.php/$1"
```

---

## nginx伪静态配置

```
location / {
      root   /var/www/html;
      index  index.html index.htm index.php;

      if (!-e $request_filename){
          rewrite ^/(.*)$ /index.php?s=/$1 last; #rewrite
      }
  }
```
